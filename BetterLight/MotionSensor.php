<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Property.php");
require_once(__DIR__ . "/../Variable.php");
require_once(__DIR__ . "/../Backing.php");

class MotionSensor 
{
    const StrMS = "MS";
    const StrScene = "Scene";

    private $module;

    public function __construct($module) {
        $this->module = $module;
    }

    // Properties

    private function MainSwitchIdProp()
    {
        return new PropertyInteger($this->module, self::StrMS . "MainSwitchId");
    }

    private function LockIdProp()
    {
        return new PropertyInteger($this->module, self::StrMS . "LockId");
    }

    private function ExternMovementIdProp()
    {
        return new PropertyInteger($this->module, self::StrMS . "ExternMovementId");
    }

    // Variables

    private function LockVar()
    {
        return new IPSVarBoolean($this->module->InstanceId(), self::StrMS . "Lock");
    
    }

    private function LockSceneVars($sceneNumber)
    {
        return new IPSVarBoolean($this->module->InstanceId(), 
            BetterBase::PersistentPrefix . 
            self::StrMS .
            self::StrScene . $sceneNumber . 
            "Lock");
    }

    // Events

    private function MainSwitchTrigger()
    {
        return new IPSEventTrigger($this->module->InstanceId(), self::StrMS . "MainSwitch" . "Trigger");
    }

    //

    public function RegisterProperties()
    {
        $this->MainSwitchIdProp()->Register();
        $this->LockIdProp()->Register();
        $this->ExternMovementIdProp()->Register();
    }

    public function RegisterVariables($sceneCount)
    {
        $this->RegisterLockVar();
        $this->RegisterSceneVars($sceneCount);
    }

    private function RegisterLockVar()
    {
        $var = $this->LockVar();
        $var->Register("BM Sperren", "~Lock"); //, self::PosLightSwitch);
        $var->EnableAction();
    }

    private function RegisterSceneVars($sceneCount)
    {
        for($i = 0; $i<$sceneCount; $i++)
        {
            $var = $this->LockSceneVars($i);
            $var->Register();
            $var->SetHidden(true);
        }
    }

    public function RegisterTriggers()
    {
        $this->MainSwitchTrigger()->Register(
            $this->MainSwitchIdProp()->Value(), 
            'BL_MSMainSwitchEvent($_IPS[\'TARGET\']);', 
            IPSEventTrigger::TypeUpdate);
    }

    public function IsMainSwitchOn()
    {
        $id = $this->MainSwitchIdProp()->Value();

        if($id == 0)
            return false;

        return GetValue($id);
    }

    public function IsLocked()
    {
        $id = $this->LockIdProp()->Value();

        if($id == 0)
            return false;

        return GetValue($id);
    }

    public function SetLock($value)
    {
        $id = $this->LockIdProp()->Value();

        if($id != 0)
        {
            EIB_Switch(IPS_GetParent($id), $value);
            $this->LockVar()->SetValue($value);
        }        
    }

    public function SetSceneLock($sceneNumber, $value)
    {
        $this->LockSceneVars($sceneNumber)->SetValue($value);
    }

    public function TriggerExternMovement()
    {
        $id = $this->ExternMovementIdProp()->Value();

        if($id != 0)
        {
            EIB_Switch(IPS_GetParent($id), true);
        }
    }

    public function SaveToScene($sceneNumber)
    {
        $value = $this->LockVar()->GetValue();
        $this->LockSceneVars($sceneNumber)->SetValue($value);
    }

    public function LoadFromScene($sceneNumber)
    {
        $currentValue = $this->IsLocked();
        $value = $this->LockSceneVars($sceneNumber)->GetValue();

        if($currentValue != $value)
        {
            $this->SetLock($value);
        }
    }
}

?>