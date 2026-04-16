<?php


namespace SendCloud\Tests\Common;


class CallHistory
{
    protected $callHistory = array();

    public function record($name, $arguments)
    {
        $this->callHistory[] = array(
            'name' => $name,
            'arguments' => $arguments,
        );
    }

    /**
     * @return array
     */
    public function getCallHistory()
    {
        return $this->callHistory;
    }

    public function reset()
    {
        $this->callHistory = array();
    }
}