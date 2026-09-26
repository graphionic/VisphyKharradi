<?php

use App\Controllers\Admin\PackageDisplayController;
use App\Models\SettingModel;
use App\Services\AdminActivityService;
use App\Services\AdminAuthService;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;

/** No database fixtures, migrations or seeders: exercise the settings boundary. */
final class PackageDisplayControllerTest extends CIUnitTestCase
{
    private function controller(array $post, SettingModel $model, bool $authenticated = true, bool $logs = false): PackageDisplayController
    {
        $auth = $this->createMock(AdminAuthService::class);
        $auth->method('getCurrentAdmin')->willReturn($authenticated ? ['id' => 1] : null);
        $activity = $this->createMock(AdminActivityService::class);
        $activity->expects($logs ? $this->once() : $this->never())->method('log');
        $request = $this->createMock(IncomingRequest::class);
        $request->method('getPost')->willReturnCallback(static fn ($key) => $post[$key] ?? null);
        $reflection = new ReflectionClass(PackageDisplayController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        foreach (['authService' => $auth, 'activity' => $activity, 'settingModel' => $model] as $name => $value) {
            $reflection->getProperty($name)->setValue($controller, $value);
        }
        $controller->initController($request, service('response'), service('logger'));
        return $controller;
    }

    public function testEveryConceptAndDeviceCombinationCanBeSaved(): void
    {
        foreach (SettingModel::ALLOWED_PACKAGE_DESIGNS as $design) {
            foreach (['grid', 'carousel'] as $desktop) {
                foreach (['grid', 'carousel'] as $tablet) {
                    foreach (['grid', 'carousel'] as $mobile) {
                        $model = $this->createMock(SettingModel::class);
                        $model->expects($this->once())->method('setPackageDisplay')
                            ->with($design, compact('desktop', 'tablet', 'mobile'))->willReturn(true);
                        $controller = $this->controller([
                            'package_design' => $design,
                            'package_layout_desktop' => $desktop,
                            'package_layout_tablet' => $tablet,
                            'package_layout_mobile' => $mobile,
                        ], $model, true, true);
                        $this->assertSame(302, $controller->update()->getStatusCode());
                    }
                }
            }
        }
    }

    public function testAdminShowsSavedConceptAndDeviceLayouts(): void
    {
        $model = $this->createMock(SettingModel::class);
        $model->method('getPackageDisplayDesign')->willReturn('concept_04');
        $model->method('getPackageDisplayLayouts')->willReturn(['desktop' => 'carousel', 'tablet' => 'grid', 'mobile' => 'grid']);
        $html = $this->controller([], $model)->index();
        $document = new DOMDocument();
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        foreach (['package_design' => 'concept_04', 'package_layout_desktop' => 'carousel', 'package_layout_tablet' => 'grid', 'package_layout_mobile' => 'grid'] as $name => $value) {
            $selected = $xpath->query('//input[@name="' . $name . '" and @checked]');
            $this->assertCount(1, $selected);
            $this->assertSame($value, $selected->item(0)->getAttribute('value'));
        }
        $this->assertCount(3, $xpath->query('//input[@name="package_design"]'));
    }

    public function testInvalidOrMissingValuesNeverSave(): void
    {
        $valid = ['package_design' => 'concept_02', 'package_layout_desktop' => 'grid', 'package_layout_tablet' => 'carousel', 'package_layout_mobile' => 'carousel'];
        foreach (array_keys($valid) as $key) {
            foreach ([null, 'invalid', ['grid']] as $invalid) {
                $model = $this->createMock(SettingModel::class);
                $model->expects($this->never())->method('setPackageDisplay');
                $response = $this->controller(array_replace($valid, [$key => $invalid]), $model)->update();
                $this->assertSame(302, $response->getStatusCode());
            }
        }
    }

    public function testUnauthenticatedRequestCannotSave(): void
    {
        $model = $this->createMock(SettingModel::class);
        $model->expects($this->never())->method('setPackageDisplay');
        $response = $this->controller([], $model, false)->update();
        $this->assertStringEndsWith('/admin/login', $response->getHeaderLine('Location'));
    }

    public function testFailedSaveIsNotLoggedAsSuccess(): void
    {
        $model = $this->createMock(SettingModel::class);
        $model->expects($this->once())->method('setPackageDisplay')->willReturn(false);
        $response = $this->controller(['package_design' => 'concept_02', 'package_layout_desktop' => 'grid', 'package_layout_tablet' => 'carousel', 'package_layout_mobile' => 'carousel'], $model)->update();
        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('Package display settings could not be saved. Please try again.', session()->getFlashdata('error'));
    }
}
