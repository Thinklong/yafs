<?php

/**
 * Copyright (c) 2016 thinklong89@gmail.com. All rights reserved.
 * Test.php
 * @auther: thinklong89@gmail.com
 * @date:${date}
 * @create Time: ${time}
 * @version ${Id}
 * @last modify time: ${LastChangedDate}
 */




class Dao_TestModel extends Base_Dao
{

    protected $_db = 'default';
    
    protected $_pk = 'id';
    
    protected $_table = 'mf_laxin';
    


    
    public function getTestLists($platform, $source, $page, $count)
    {
        $condition = [];
        null !== $platform && $condition['city_en'] = $platform;
        null !== $source && $condition['mobile'] = $source;
        //null !== $start_time && $condition['cdate'] = [['egt', $start_time], ['elt', $end_time]];
        
        $where = $condition ? $this->where($condition) : '';
        
        $opts = [
            'where' => $where,
            'start' => ($page - 1) * $count,
            'limit' => $count,
            'order' => $this->_pk . ' asc',
        ];
        $result = $this->find($opts);
        return $result;
    }
    
    
    /**
     * count总数
     * @param unknown $platform
     * @param unknown $source
     * @return Ambigous <number, boolean, unknown>
     */
    public function total($platform, $source)
    {
        $condition = [];
        null !== $platform && $condition['city_en'] = $platform;
        null !== $source && $condition['mobile'] = $source;
        //null !== $start_time && $condition['cdate'] = [['egt', $start_time], ['elt', $end_time]];
    
        $where = $condition ? $this->where($condition) : '';
        $result = $this->count($where);
        return $result;
    }
    
    

}
