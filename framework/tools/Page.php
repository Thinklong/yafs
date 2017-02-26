<?php
/**
 * 分页类
 *
 *
 */
class Tools_page
{
    //总记录数
    private $_total_count;
    //总页数
    private $_total_page;
    //每页显示数
    private $_page_num;
    //单前页
    private $_current_page;
    //上一页
    private $_prev_page;
    //下一页
    private $_next_page;
    //链接
    private $_url;
    //分页间隔
    private $_interval;
        
    public function __construct($total_count = 0, $current_page, $page_num = 10, $interval = 5)
    {
        $this->_total_count = $total_count;
        $this->_page_num = $page_num;
        $this->_interval = $interval;
        $this->_total_page = ($this->_total_count > 0) ? ceil($this->_total_count / $this->_page_num) : 1;
        $this->_current_page = $current_page;
        $this->_current_page = ($this->_current_page < 1) ? 1 : $this->_current_page;
        $this->_current_page = ($this->_current_page > $this->_total_page) ? $this->_total_page : $this->_current_page;
        $this->_prev_page = ($this->_current_page <= 1) ? 1 : ($this->_current_page - 1);
        $this->_next_page = ($this->_current_page >= $this->_total_page) ? $this->_total_page : ($this->_current_page + 1);
        $this->set_url(); 
    }
    
    /**
     * 设置url
     * @param string $url
     */
    public function set_url($url = '')
    {
        if(!empty($url))
        {
            $this->_url = $this->_url;            
        }
        else
        {
            $uri = $_SERVER['REQUEST_URI'];
            $uri_arr = explode('?', $uri);
            $parameters = $_GET;
            if(isset($parameters['page']))unset($parameters['page']);
            $parameters = http_build_query($parameters);
            $this->_url = empty($parameters) ? $uri_arr[0] : $uri_arr[0] . '?' . $parameters;
        }
        $this->_url .= (strpos($this->_url,"?") === false) ? '?' : '&';
    }
    
    /**
     * 返回总页数
     * @return number
     */
    public function get_total_page()
    {
        return $this->_total_page;
    }
    
    /**
     * 返回当前页
     */
    public function get_current_page()
    {
        return $this->_current_page;
    }
    
    /**
     * 返回上一页
     */
    public function get_prev_page()
    {
        return $this->_prev_page;
    }
    
    /**
     * 返回下一页
     */
    public function get_next_page()
    {
        return $this->_next_page;
    }
    
    /**
     * 返回分页条件
     * @return string
     */
    public function get_limit()
    {
        $start = ($this->_current_page - 1) * $this->_page_num;
        return $start . ',' . $this->_page_num;
    }
    
    /**
     * 返回URL
     * @return string
     */
    public function get_url()
    {
        return $this->_url;
    }
    
    /**
     * 获取分页HTML
     * @return string
     */
    public function get_html()
    {
        $start = $this->_current_page > $this->_interval ? $this->_current_page - $this->_interval : 1;
        $end = $this->_current_page + $this->_interval;
        $end = $end > $this->_total_page ? $this->_total_page : $end;
        
        $html = "<ul class='pagination no-margin' style='padding-top:10px;'> ";
        if($this->_current_page > $this->_interval)
        {
            $html .= "<li><a href=" . $this->_url . "page=1>首页</a></li>";
        }
        if($this->_current_page != $this->_prev_page)
        {
            $html .= '<li><a href=' . $this->_url . 'page=' . $this->_prev_page . '>&laquo;</a></li>'; //上一页
        }
        
        for($i = $start; $i <= $end; $i++)
        {
            if($i == $this->_current_page)
            {
                $html .= '<li class="active"><a href="javascript:void(0);">' . $i . '</a></li>';
            }
            else
            {
                $html .= '<li><a href=' . $this->_url . 'page=' . $i . '>' . $i . '</a></li>';
            }
        }
        
        if($this->_current_page != $this->_total_page)
        {
            $html .= '<li><a href=' . $this->_url . 'page=' . $this->_next_page . '>&raquo;</a></li>'; //下一页
        }
        if(($this->_current_page + $this->_interval) < $this->_total_page)
        {
            $html .= '<li><a href=' . $this->_url . 'page=' . $this->_total_page . '>末页</a></li>';
        }
        //$html .= '<li>共' . $this->_total_count . '条记录&nbsp;' . $this->_total_page . '页</li>';

        $html .= "</ul>";
        
        return $html;
    }
    
    /**
     * 获取AJAX HTML
     * @return string
     */
    public function get_ajax_html()
    {
        $start = $this->_current_page > $this->_interval ? $this->_current_page - $this->_interval : 1;
        $end = $this->_current_page + $this->_interval;
        $end = $end > $this->_total_page ? $this->_total_page : $end;

        $html = "<ul class='pagination no-margin' style='padding-top:10px;'> ";
        if($this->_current_page > $this->_interval)
        {
            $html .= '<li><a href="javascript:void(0);" data_page=1>首页</a></li>';
        }
        if($this->_current_page != $this->_prev_page)
        {
            $html .= '<li><a href="javascript:void(0);" data_page=' . $this->_prev_page . '>&laquo;</a></li>';
        }
    
        for($i = $start; $i <= $end; $i++)
        {
            if($i == $this->_current_page)
            {
                $html .= '<li class="active"><a class="cur" href="javascript:void(0);" data_page=' . $i . '>' . $i . '</a>/li>';
            }
            else
            {
                $html .= '<li><a href="javascript:void(0);" data_page=' . $i . '>' . $i . '</a></li>';
            }
        }
    
        if($this->_current_page != $this->_total_page)
        {
            $html .= '<li><a href="javascript:void(0);" data_page=' . $this->_next_page . '>&raquo;</a></li>';
        }
        if(($this->_current_page + $this->_interval) < $this->_total_page)
        {
            $html .= '<li><a href="javascript:void(0);" data_page=' . $this->_total_page . '>末页</a></li>';
        }
    
        //$html .= '共' . $this->_total_count . '条记录&nbsp;' . $this->_total_page . '页';

        $html .= "</ul>";

        return $html;
    }
}