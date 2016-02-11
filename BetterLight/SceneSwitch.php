<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Property.php");
require_once(__DIR__ . "/../Variable.php");

class SceneSwitchArray {
    private $size;
    private $module;

    public function __construct($module, $size, $type)
    {
        $this->module = $module;
        $this->size = $size;
        $this->type = $type;
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
        $script = "BL_SetScene(" . $this->module->InstanceId() . ", " . $this->SceneNumber() . ");";
        $this->Trigger()->Register($this->SwitchId(), $script, IPSEventTrigger::TypeUpdate);
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