<?php
class RightsPlugin extends Yaf_Plugin_Abstract
{
    public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

    }

    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

        $controller = $request->getControllerName();

        $action = $request->getActionName();

        $ctlModel = new Service_Manager_ControllerModel();
        
        $userModel = new Service_Manager_UserModel();
        
        $ctlInfo = $ctlModel->getControllerInfo('',$controller);

        if(empty($ctlInfo))
        {
            AppResponse::response(Public_Error::ERR_CONTROLLER_NOT_FOUND);//找不到控制器
        }

        $actInfo = $ctlModel->getControllerInfo('',$action,$ctlInfo['id']);

        if(empty($actInfo))
        {
            AppResponse::response(Public_Error::ERR_ACTION_NOT_FOUND);//没有相关操作权限
        }

        if(intval($actInfo['is_right']) == 1) //方法需要权限判断
        {
            $userInfo = $userModel->is_login();

            if(!$userInfo) //用户未登陆时 跳转到登陆页面
            {
                AppResponse::redirect('/manager/user/login.html');
            }

            //查询方法是否过滤权限
            $checkResult = $userModel->checkRights($ctlInfo['id'],$actInfo['id']);

            if(!$checkResult)
            {
                AppResponse::response(Public_Error::ERR_USER_NO_PRIVILEGE);//没有相关操作权限
            }
        }

    }

    public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

    }

    public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

    }

    public function preResponse(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
    }

    public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

    }

    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

    }
}
