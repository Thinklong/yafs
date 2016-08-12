<?php
class ParamsValidator {

    /**
    private $method_map = [
                'allow_fields' => 'verifyFields',
                'rules'  => 'verifyFieldValues',
        ];

    private $config;

    public function __construct($config) {
        $this->config = $config;
    } 

    public function validator() {
        foreach ($this->config as $key => $val) {
            $method = $this->getMethod($key);
            if (null == $method) continue;

            $result = $this->$method();

            if (false == $result) return false;
        }

        return true;
    }

    public function getMethod($key) {
        if (false == array_key_exists($key, $this->method_map)) return null;

        return $this->method_map[$key];
    }

    public function verifyFields() {
        $allow_fields = $this->config['allow_fields'];
        $array_diff = array_diff(array_keys(array_merge($_GET, $_POST)), $allow_fields);
        
        if (!empty($array_diff)) {
            return false;
        }

        return true;
    }
    **/

    public static function verifyFieldValues($rules, $object = null) {
        
        $validator = new Validator();

        $validation = $validator::validate($rules, $object);

        return $validation->isValid();
    }
}
