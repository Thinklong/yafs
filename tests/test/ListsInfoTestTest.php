<?php


class ListsInfoTestTest extends Base_Test
{
    
    protected $module = 'test';
    
    protected $appRole = 'qiyulong';
    
    public function testCreate()
    {
        $data = [];
        $data['source'] = 'test';
        $data['platform'] = '1';
        
        $result = null;
        $this->invoke('lists_info', $data, 'POST', $result);
        print_r(($result));
    }

}