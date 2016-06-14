<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Property.php");
require_once(__DIR__ . "/../Variable.php");

require_once(__DIR__ . "/../IPS/IPS.php");

class SceneSwitchArray {
    private $size;
    private $module;

    public function __construct($module, $size)
    {
        $this->module = $module;
        $this->size = $size;
    }

    public function Count()
    {
        $count = 0;
        
        for($i=0; $i<$this->size; $i++)
        {
            $light = $this->At($i);

            if(!$light->IsDefined())
            {
                return $count;
            }

            $count++;
        }

        return $count;
    }

    public function At($index)
    {
        return new SceneSwitch($this->module, $index);
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

class SceneSwitch
{
    private $index;
    private $module;

    const Size = 4;
    const StrPrefix = "SceneSwitch";

    public function __construct($module, $index) {
        $this->module = $module;
        $this->index = $index;
    }

    // Properties

    private function SwitchIdProp()
    {        
        return new PropertyInteger($this->module, self::StrPrefix . $this->index . "SwitchId");
    }   

    private function SceneNumberProp()
    {        
        return new PropertyInteger($this->module, self::StrPrefix . $this->index . "SceneNumber");
    }

    // Events

    private function Trigger()
    {
        return new IPSEventTrigger($this->module->InstanceId(), self::StrPrefix . $this->index . "Trigger");
    }

    public function RegisterProperties()
    {
        $this->SwitchIdProp()->Register();
        $this->SceneNumberProp()->Register();
    }

    public function RegisterTriggers()
    {
        $instanceId = $this->module->InstanceId();
        $sceneNumber = $this->SceneNumber();

        if($sceneNumber == 0)
        {
            $script = "BL_TurnOff($instanceId);";
        }
        else
        {
            $script = "BL_ToggleScene($instanceId, $sceneNumber);";
        }

        $this->Trigger()->Register("", $this->SwitchId(), $script, IPSEventTrigger::TypeUpdate);
    }

    public function SwitchId()
    {
        return $this->SwitchIdProp()->Value();
    }

    public function SceneNumber()
    {
        return $this->SceneNumberProp()->Value();
    }
    
    public function IsDefined()
    {
        return $this->SwitchId() != 0;
    }
}

?>