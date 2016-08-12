<?php


class ListsTestTest extends Base_Test
{
    
    protected $module = 'test';
    
    protected $appRole = 'qiyulong';
    
    public function testCreate()
    {
        $data = [];//'month', 'platform', 'sources', 'page', 'count'
        $data['source'] = 'test';
        $data['platform'] = '1';
        
        $result = null;
        $this->invoke('lists', $data, 'POST', $result);
	print_r($result);
        print_r(json_encode($result));
    }

}
