<?php
class LevelTest extends Base_Test
{

    protected $module = 'test';

    protected $appRole = 'qiyulong';

    public function testCreate()
    {
        $data = [];
        $data['platform'] = 1;
        $data['source'] = 1;

        $result = null;
        $this->invoke('level/lists', $data, 'POST', $result);var_dump($result);
        print_r(json_encode($result));
        
    }

}