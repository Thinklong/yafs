<?php


/**
 * 计时器
 * Timer::start();
 * Timer::point('第一个节点名称');
 * Timer::point('第二个节点名称');
 * Timer::stop();//可省略
 * $spent = Timer::spent();
 *
 */
class Timer
{
    /**
     * 计时器开始时间
     * @var time
     */
    protected static $startTime = 0;
    /**
     * 节点时间
     */
    protected static $pointsTime = [];
    /**
     * 计时器结束时间
     * @var time
     */
    protected static $stopTime = 0;
    /**
     * 花费时间记录
     */
    protected static $spentTime = [];
    
    /**
     * 计时器开始
     */
    public static function start()
    {
        self::$startTime = microtime();
    }
    
    
    /**
     * 计时器添加节点
     * @param string $keyword 每个节点的名称
     */
    public static function point($keyword = null)
    {
        if (empty($keyword))
        {
            self::$pointsTime[] = microtime();
        }
        else
        {
            self::$pointsTime[$keyword] = microtime();
        }
    }
    
    
    /**
     * 计时器结束
     */
    public static function stop()
    {
        self::$stopTime = microtime();
        
    }
    
    
    /**
     * 计时器花费时长计算
     * @return multitype: 
     */
    public static function spent()
    {
        if (self::$stopTime == 0)
        {
            self::stop();
        }
        if (self::$spentTime)
        {
            return self::$spentTime;
        }
        else
        {
            $prev = 0;
            $start = self::getTime(self::$startTime);
            foreach (self::$pointsTime as $key=>$val)
            {
                $startSpent = $prevSpent = '';
                $point = self::getTime($val);
                !is_numeric($key) && $startSpent = "[{$key}]";
                $startSpent .= "与Timer开始总共耗时：" . substr(bcsub($point, $start, 4), 0, 8) . "秒。";
                if ($prev != 0)
                {
                    !is_numeric($key) && $prevSpent = "[{$key}]";
                    $prevSpent .= "与上一个节点耗时：" . substr(bcsub($point, $prev, 4), 0, 8) . "秒。";
                }
                $prev = $point;
                self::$spentTime[] = $startSpent . $prevSpent;
            }
            self::$spentTime[] = "Timer开始总消耗时间：" . substr(bcsub(self::getTime(self::$stopTime), $start, 4), 0, 8) . "秒。";
            return self::$spentTime;
        }
    }
    
    /**
     * 获取双精度时间
     * @param unknown $time
     * @return number
     */
    private static function getTime($time)
    {
        list($micro, $second) = explode(" ", $time);
        $doubleTime = doubleval($micro) + $second;
        return $doubleTime;
    }
    
    
    
}