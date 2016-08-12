<?php
class RoutePlugin extends Yaf_Plugin_Abstract {

    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response) {

        $url = $request->getRequestUri();
        $pattern = '#^/([^/]*)/(.*)#';
        if (preg_match($pattern, $url, $matches)) {
            $controllerName = trim($matches[1]);
            $method = trim($matches[2]);
            $method = $this->getMethod($method);
            $method = strtolower(str_replace('_', '', $method));

            $request->setRequestUri("/$controllerName/$method");
            $request->setParam('method', $method);
        }
    }

    protected function getMethod($methodName) {
        $hasSuffix = strpos($methodName, '.');
        if ($hasSuffix !== false) {
            $methodName = substr($methodName, 0, $hasSuffix);
        }
        Yaf_Registry::set('method', $methodName);
        return $methodName;
    }
} 
