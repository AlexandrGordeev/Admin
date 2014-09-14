<?php

namespace Modules\Admin\Controllers;

use Mindy\Base\Mindy;
use Mindy\Pagination\Pagination;
use Modules\Core\Components\UserLog;
use Modules\Core\Controllers\BackendController;

class AdminController extends BackendController
{
    public function allowedActions()
    {
        return ['index'];
    }

    public function actionIndex()
    {
        $pager = new Pagination([
            'source' => UserLog::read(100),
        ]);
        $messages = $pager->paginate();
        echo $this->render('admin/index.html', [
            'messages' => $messages,
            'pager' => $pager
        ]);
    }

    public function actionList($module, $adminClass)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        if ($this->can($module, $adminClass, 'list') === false) {
            $this->error(403);
        }

        $admin = new $className;
        if (is_string($admin->getModel()) && class_exists($admin->getModel()) === false) {
            $this->error(404);
        }

        if ($this->r->http->isPostRequest && $this->r->post->get('action')) {
            $action = $_POST['action'];
            unset($_POST['action']);
            $admin->$action($_POST);
            Mindy::app()->end();
        }

        $admin->setParams($_GET);
        $moduleName = $admin->getModel()->getModuleName();

        $context = $admin->index();
        $out = $this->render($admin->indexTemplate, array_merge([
            'actions' => $admin->getActions(),
            'module' => $admin->getModule(),
            'moduleName' => $moduleName,
            'modelClass' => $admin->getModel()->classNameShort(),
            'adminClass' => $adminClass,
            'admin' => $admin,
        ], $context));

        $this->setBreadcrumbs($context['breadcrumbs']);

        if ($this->r->isAjax) {
            echo $out;
        } else {
            echo $this->render('admin/admin/list.html', array_merge(['adminClass' => $adminClass], [
                'module' => $admin->getModule(),
                'modelClass' => $admin->getModel(),
                'out' => $out,
                'admin' => $admin
            ]));
        }
    }

    public function formatBreadcrumbs(array $menu = [], $admin)
    {
        $name = Mindy::app()->getModule($admin->getModule()->id)->getName();
        foreach ($menu as $item) {
            if ($item['name'] == $name) {
                return $item['items'];
            }
        }

        return [];
    }

    protected function getAdminClassName($module, $adminClass)
    {
        $className = "\\Modules\\" . ucfirst(strtolower($module)) . "\\Admin\\" . $adminClass;
        if (class_exists($className)) {
            return $className;
        }

        return null;
    }

    public function actionCreate($module, $adminClass)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        if ($this->can($module, $adminClass, 'create') === false) {
            $this->error(403);
        }

        $admin = new $className();
        $context = $admin->create($_POST, $_FILES);
        $this->setBreadcrumbs(array_merge([
            [
                'name' => $admin->getVerboseName(),
                'url' => Mindy::app()->urlManager->reverse('admin.list', [
                        'adminClass' => $adminClass,
                        'module' => $module
                    ])
            ]
        ], $context['breadcrumbs']));
        echo $this->render('admin/admin/create.html', array_merge(['module' => $module, 'adminClass' => $adminClass], $context));
    }

    public function actionUpdate($module, $adminClass, $id)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        if ($this->can($module, $adminClass, 'update', ['pk' => $id]) === false) {
            $this->error(403);
        }

        $admin = new $className();
        $context = $admin->update($id, $_POST, $_FILES);
        $this->setBreadcrumbs(array_merge([
            [
                'name' => $admin->getVerboseName(),
                'url' => Mindy::app()->urlManager->reverse('admin.list', [
                        'adminClass' => $adminClass,
                        'module' => $module
                    ])
            ]
        ], $context['breadcrumbs']));
        echo $this->render('admin/admin/update.html', array_merge(['module' => $module, 'adminClass' => $adminClass], $context));
    }

    public function actionDelete($module, $adminClass, $id)
    {
        $className = $this->getAdminClassName($module, $adminClass);
        if ($className === null) {
            $this->error(404);
        }

        if ($this->can($module, $adminClass, 'delete', ['pk' => $id]) === false) {
            $this->error(403);
        }

        $admin = new $className();
        $admin->remove([$id]);
        $this->redirect(Mindy::app()->urlManager->reverse('admin.list', ['module' => $module, 'adminClass' => $adminClass]));
    }

    protected function can($module, $adminClass, $actionId, $params = [])
    {
        $code = $module . '.admin.' . strtolower($adminClass) . '.' . $actionId;
        return Mindy::app()->user->can($code, $params);
    }
}
