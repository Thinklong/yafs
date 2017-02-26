<?php
/**
 * 布局插件
 *
 */


/**
 *  Yaf的Hook
 *
 *  触发顺序    名称                   触发时机                   说明
 *  1   routerStartup          在路由之前触发           这个是7个事件中, 最早的一个. 但是一些全局自定的工作, 还是应该放在Bootstrap中去完成
 *  2   routerShutdown         路由结束之后触发      此时路由一定正确完成, 否则这个事件不会触发
 *  3   dispatchLoopStartup    分发循环开始之前被触发
 *  4   preDispatch            分发之前触发            如果在一个请求处理过程中, 发生了forward, 则这个事件会被触发多次
 *  5   postDispatch           分发结束之后触发      此时动作已经执行结束, 视图也已经渲染完成. 和preDispatch类似, 此事件也可能触发多次
 *  6   dispatchLoopShutdown   分发循环结束之后触发        此时表示所有的业务逻辑都已经运行完成, 但是响应还没有发送
 *
 */
class LayoutPlugin extends Yaf_Plugin_Abstract
{

    private $_layoutFile;
    private $_layoutVars = array();

    public function __construct($layoutFile)
    {
        $this->_layoutFile = $layoutFile;
    }

    public function __set($name, $value)
    {
        $this->_layoutVars[$name] = $value;
    }

    public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

    }

    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {

    }

    public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        //var_dump('aweawewewe');exit;
        if (Yaf_Registry::has(Base_Constants::WHETHER_LOAD_LAYOUT))
        {
            return;
        }

        /* get the body of the response */
        $body = $response->getBody();

        /*clear existing response*/
        $response->clearBody();

        /* wrap it in the layout */
        $layout = new Smarty_Adapter(null, Yaf_Registry::get("config")->get("smarty"));

        $layout->content = $body;
        $layout->assign('layout', $this->_layoutVars);

        $ctlName = $request->getControllerName();
        $actionName = $request->getActionName();

        $composerList = $this->settopNav();
        $layout->assign('composerList', $composerList);

        $breadCrumb = $this->setBreadCrumb($ctlName, $actionName);
        $layout->assign('bread_crumb', $breadCrumb);

        $ctlInfo = $this->setActionMenu($ctlName, $actionName);


        $layout->assign('ctl_list', $ctlInfo);
        $layout->assign('user_name', isset($_COOKIE['user_name']) ? $_COOKIE['user_name'] : 'Not Login');
        $layout->assign('action_name', $actionName);
        $layout->assign('ctl_name', str_replace('_', '/', $ctlName));

        //按需加载js等文件
        $layout->assign($this->getDemandLoading());

        /* set the response to use the wrapped version of the content */
        $response->setBody($layout->render($this->_layoutFile));
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

    public function getDemandLoading()
    {
        $load = [];
        Yaf_Registry::has(Base_Constants::WHETHER_LOAD_CHARTS) && $load[Base_Constants::WHETHER_LOAD_CHARTS] = true;
        return $load;
    }

    public function setTopNav()
    {
        $rightControl = new Service_Manager_UserModel();
        $composerList = $rightControl->getMenu();
        krsort($composerList, 2);
        foreach ($composerList as $key=> $val)
        {
            krsort($val['controllers'], 2);
            $composerList[$key] =$val;
        }
        return $composerList;
    }

    public function setBreadCrumb( $ctlName, $actionName)
    {
        $cmpModel = new Service_Manager_ComposerModel();
        $ctlModel = new Service_Manager_ControllerModel();
        $ctlInfo = $ctlModel->getControllerInfo('', $ctlName);
        $cmpInfo = $cmpModel->getComposerById($ctlInfo['compose_id']);
        $actions = $ctlModel->getActionList($ctlInfo['id']);
        foreach ($actions as $actionsInfo)
        {
            if ($actionsInfo['func_name'] == $actionName)
            {
                $actionInfo =$actionsInfo['func_name_cn'];
            }
        }
       return [
           'module' => $cmpInfo['cn_name'],
           'controller' => $ctlInfo['func_name_cn'],
           'action' => $actionInfo,

       ];
    }

    /**
     * 设置action显示菜单
     * @param $ctlName controller 名称
     * @param $actionName actino 名称
     */
    public function setActionMenu($ctlName, $actionName)
    {
        $userModel = new Service_Manager_UserModel();
        $ctlInfo = $userModel->getActionMenu($ctlName);

        // 显示菜单的action 一维数组
        if (!empty($ctlInfo))
        {
            foreach ($ctlInfo as $key => $value)
            {
                $actionArr[] = $value['func_name'];
            }

        } else
        {
            $actionArr = [];
            //exit('没有对应权限');
        }

        //处理没有标记为菜单选项的action情况
        if (!in_array($actionName, $actionArr))
        {
            $refer = rtrim(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '', '/');

            $refer = str_replace('.html', '', $refer);

            $refer_action = substr($refer, (strrpos($refer, '/') + 1));
            // 防止非正常跳转丢失action状态
            if (!in_array($refer_action, $actionArr))
            {
                $_COOKIE['active_action'] = array_shift($actionArr);
            } else
            {
                $_COOKIE['active_action'] = $refer_action;
            }

        } else
        {
            $_COOKIE['active_action'] = $actionName;
        }
        return $ctlInfo;
    }
}
