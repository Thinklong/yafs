<?php
declare(ticks = 1);
class SignalDemo extends CliBase{

    public function init() {
        $this->className = __CLASS__;

        parent::registerSignal();
    }
    public function execute() {

        while($this->state) {
            echo 'running ' . time() . PHP_EOL;
            sleep(1);
        }

        echo '平滑关闭' . PHP_EOL;
    }
}
