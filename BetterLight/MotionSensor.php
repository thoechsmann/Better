<?
declare(strict_types=1);

require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Backing.php");
require_once(__DIR__ . "/../IPS/IPS.php");

class MotionSensor 
{
    const StrMS = "MS";
    const StrScene = "Scene";
    const BMProfile = "BL_Zwangsfuehrung";

    const StateAuto = 0;
    const StateAlwaysOff = 1;
    const StateAlwaysOn = 2;

    private BetterBase $module;

    public function __construct(BetterBase $module) {
        $this->module = $module;
    }

    // Properties

    private function MainSwitchIdProp()
    {
        return new IPSPropertyInteger($this->module, self::StrMS . "MainSwitchId");
    }

    private function LockOffIdProp()
    {
        return new IPSPropertyInteger($this->module, self::StrMS . "LockOffId");
    }

    private function LockOnIdProp()
    {
        return new IPSPropertyInteger($this->module, self::StrMS . "LockOnId");
    }

    private function ExternMovementIdProp()
    {
        return new IPSPropertyInteger($this->module, self::StrMS . "ExternMovementId");
    }

    // Variables

    private function LockVar()
    {
        return new IPSVarInteger($this->module->InstanceId(), self::StrMS . "Lock");
    }

    private function LockSceneVars($sceneNumber)
    {
        return new IPSVarInteger($this->module->InstanceId(), 
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
        $this->LockOffIdProp()->Register();
        $this->LockOnIdProp()->Register();
        $this->ExternMovementIdProp()->Register();
    }

    public function RegisterVariables(int $sceneCount, int $position)
    {
        if(!$this->IsDefined())
        {
            return;
        }

        $this->RegisterProfiles();
        $this->RegisterSceneVars($sceneCount);
        $this->RegisterLockVar($position);
    }

    private function RegisterProfiles()
    {
        if(!IPS_VariableProfileExists(self::BMProfile))
        {
            IPS_CreateVariableProfile(self::BMProfile, 1);
            IPS_SetVariableProfileAssociation(self::BMProfile, self::StateAuto, "Bewegung", "", 0);
            IPS_SetVariableProfileAssociation(self::BMProfile, self::StateAlwaysOff, "Immer Aus", "", 0);
            IPS_SetVariableProfileAssociation(self::BMProfile, self::StateAlwaysOn, "Immer An", "", 0);
        }
    }

    private function RegisterLockVar(int $position)
    {
        $var = $this->LockVar();
        $var->Register("BM Sperren", self::BMProfile, $position);
        $this->module->EnableAction($var->Ident());
    }

    private function RegisterSceneVars(int $sceneCount)
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
        if(!$this->IsDefined())
        {
            return;
        }

        $this->MainSwitchTrigger()->Register("",
            $this->MainSwitchIdProp()->Value(), 
            'BL_MSMainSwitchEvent($_IPS[\'TARGET\']);', 
            IPSEventTrigger::TypeUpdate);
    }

    public function IsDefined()
    {
        return $this->MainSwitchIdProp()->Value() > 0;
    }

    public function IsMainSwitchOn()
    {
        $id = $this->MainSwitchIdProp()->Value();
        return GetValueBoolean($id);
    }

    public function LockState()
    {
        if(!$this->IsDefined())
        {
            return false;
        }

        return $this->LockVar()->Value();
    }

    public function SetLockState(int $value)
    {
        if(!$this->IsDefined())
        {
            return;
        }

        if($this->LockVar()->Value() == $value)
            return;

        $lockOnId = $this->LockOnIdProp()->Value();
        $lockOffId = $this->LockOffIdProp()->Value();

        switch($value)
        {
            case self::StateAuto:
                EIB_Switch(IPS_GetParent($lockOnId), false);
                EIB_Switch(IPS_GetParent($lockOffId), false);
                break;
            case self::StateAlwaysOn:
                EIB_Switch(IPS_GetParent($lockOffId), false);
                EIB_Switch(IPS_GetParent($lockOnId), true);
                break;
            case self::StateAlwaysOff:
                EIB_Switch(IPS_GetParent($lockOnId), false);
                EIB_Switch(IPS_GetParent($lockOffId), true);
                break;
        }

        $this->LockVar()->SetValue($value);
    }

    public function SetSceneLock(int $sceneNumber, int $value)
    {
        if(!$this->IsDefined())
        {
            return;
        }

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

    public function SaveToScene(int $sceneNumber)
    {
        if(!$this->IsDefined())
        {
            return;
        }

        $value = $this->LockVar()->Value();
        $this->LockSceneVars($sceneNumber)->SetValue($value);
    }

    public function LoadFromScene(int $sceneNumber)
    {
        if(!$this->IsDefined())
        {
            return;
        }

        $value = $this->LockSceneVars($sceneNumber)->Value();
        $this->SetLockState($value);
    }

    public function RequestAction(string $ident, $value) 
    {
        if($ident == $this->LockVar()->Ident())
        {
            $this->SetLockState($value);
            return true;
        }

        return false;
    }

}

?>