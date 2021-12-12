<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../IPS/IPS.php");

class ShutterControlArray {
    private int $size;
    private $module;

    public function __construct($module, int $size)
    {
        $this->module = $module;
        $this->size = $size;
    }

    public function Count()
    {
        $count = 0;
        
        for($i=0; $i<$this->size; $i++)
        {
            $obj = $this->At($i);

            if(!$obj->IsDefined())
            {
                return $count;
            }

            $count++;
        }

        return $count;
    }

    public function At(int $index)
    {
        return new ShutterControl($this->module, $index);
    }

    public function RegisterProperties()
    {
        for($i=0; $i<$this->size; $i++)
        {
            $this->At($i)->RegisterProperties();
        }
    }

    public function RegisterTriggers()
    {
        for($i=0; $i<$this->Count(); $i++)
        {
            $this->At($i)->RegisterTriggers();
        }
    }
}

class ShutterControl
{
    const StrPrefix = "ShutterControl";

    private int $index;
    private $module;

    public function __construct($module, int $index) {
        $this->module = $module;
        $this->index = $index;
    }

    // Properties

    private function UpDownIdProp()
    {        
        return new IPSPropertyInteger($this->module, self::StrPrefix . $this->index . "UpDownIdProp");
    }   

    private function StopIdProp()
    {        
        return new IPSPropertyInteger($this->module, self::StrPrefix . $this->index . "StopIdProp");
    }

    // Events

    private function UpDownTrigger()
    {
        return new IPSEventTrigger($this->module->InstanceId(), self::StrPrefix . $this->index . "UpDownTrigger");
    }

    private function StopTrigger()
    {
        return new IPSEventTrigger($this->module->InstanceId(), self::StrPrefix . $this->index . "StopTrigger");
    }

    public function RegisterProperties()
    {
        $this->UpDownIdProp()->Register();
        $this->StopIdProp()->Register();
    }

    public function RegisterTriggers()
    {
        $this->UpDownTrigger()->Register("", $this->UpDownId(), 'BSN_UpDownEvent($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', IPSEventTrigger::TypeUpdate);

        $this->StopTrigger()->Register("", $this->StopId(), 'BSN_StopEvent($_IPS[\'TARGET\']);', IPSEventTrigger::TypeUpdate);
    }

    public function UpDownId()
    {
        return $this->UpDownIdProp()->Value();
    }

    public function StopId()
    {
        return $this->StopIdProp()->Value();
    }
    
    public function IsDefined()
    {
        return $this->UpDownId() != 0;
    }
}

?>