<?
declare(strict_types=1);

require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../IPS/IPS.php");

class SceneSwitchArray {
    private int $size;
    private BetterBase $module;

    public function __construct(BetterBase $module, int $size)
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

    public function At(int $index)
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
    private int $index;
    private BetterBase $module;

    const Size = 4;
    const StrPrefix = "SceneSwitch";

    public function __construct(BetterBase $module, int $index) {
        $this->module = $module;
        $this->index = $index;
    }

    // Properties

    private function SwitchIdProp()
    {
        return new IPSPropertyInteger($this->module, self::StrPrefix . $this->index . "SwitchId");
    }

    private function SceneNumberProp()
    {
        return new IPSPropertyInteger($this->module, self::StrPrefix . $this->index . "SceneNumber");
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
