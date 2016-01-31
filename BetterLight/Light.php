<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Property.php");
require_once(__DIR__ . "/../Variable.php");
require_once(__DIR__ . "/../Backing.php");

class Light {
    const StrLight = "Light";
    const StrScene = "Scene";

    protected $index;
    protected $module;

    public function __construct($module, $index) {
        $this->module = $module;
        $this->index = $index;
    }

    // Properties

    protected function NameProp()
    {        
        return new PropertyString($this->module, self::StrLight . $this->index . "Name");
    }   

    protected function SwitchIdProp()
    {        
        return new PropertyInteger($this->module, self::StrLight . $this->index . "SwitchId");
    }

    // Variables

    protected function DisplayVar()
    {
        return new Variable($this->module, 
            self::StrLight . $this->index .
            "DisplayVar");
    }

    protected function IsDisplayVar($ident)
    {
        return $ident == $this->DisplayVar()->Ident();
    }

    protected function SceneVars($sceneNumber)
    {
        return new Variable($this->module, 
            BetterBase::PersistentPrefix . 
            self::StrLight . $this->index . 
            self::StrScene . $sceneNumber . 
            "Value");
    }

    // Register

    public function RegisterProperties()
    {
        $this->NameProp()->Register();
        $this->SwitchIdProp()->Register();
    }

    //

    public function Name()
    {
        return $this->NameProp()->Value();
    }

    public function IsDefined()
    {
        return $this->Name() != "";
    }

}

class DimLight {
    const Size = 6;

    static public function GetIndexForDisplayIdent($ident)
    {
        for($i = 0; $i<self::Size; $i++)
        {
            $var = new DimLight(0, $i);
            if($var->IsDisplayVar($ident))
                return $i;
        }

        return false;
    }

    public function __construct($module, $index) {
        parent::__construct($module, $index);
    }

    // Properties

    private function SetValueIdProp()
    {        
        return new PropertyInteger($this->module, Light::StrLight . $this->index . "SetValueId");
    }

    private function StatusValueIdProp()
    {        
        return new PropertyInteger($this->module, Light::StrLight . $this->index . "StatusValueId");
    }

    // Backings

    public function DisplayVarBacking()
    {
        $getterId = $this->StatusValueIdProp()->Value();
        $setterId = $this->SetValueIdProp()->Value();
        $displayIdent = $this->DisplayVar()->Ident();
        return new Backing($this->module, $displayIdent, $getterId, $setterId, Backing::EIBTypeScale);
    }

    // Register

    public function RegisterProperties()
    {
        parent::RegisterProperties();

        $this->SetValueIdProp()->Register();
        $this->StatusValueIdProp()->Register();
    }

    public function RegisterVariables($sceneCount)
    {
        $this->RegisterDisplayVar();
        $this->RegisterSceneVars($sceneCount);
    }

    private function RegisterDisplayVar()
    {
        $name = $this->NameProp()->Value();
        $var = $this->DisplayVar();
        $var->RegisterVariableInteger($name, "~Intensity.100"); //, self::PosLightSwitch);
        $var->EnableAction();
    }

    private function RegisterSceneVars($sceneCount)
    {
        for($i = 0; $i<$sceneCount; $i++)
        {
            $sceneLight = $this->SceneVars($i);
            $sceneLight->RegisterVariableInteger();
            $sceneLight->SetHidden(true);
        }
    }

    public function RegisterTriggers()
    {
        $backing = $this->DisplayVarBacking();
        $backing->RegisterTrigger('BL_CancelSave($_IPS[\'TARGET\']);');
    }

    //

    public function TurnOff()
    {
        $this->DisplayVarBacking()->SetValue(false);
    }

    public function SaveToScene($sceneNumber)
    {
        $value = $this->DisplayVar()->GetValue();
        $this->SceneVars($sceneNumber)->SetValue($value);
    }

    public function LoadFromScene($sceneNumber, $triggerIdent = "", $triggerValue = 0)
    {
        $value = $this->SceneVars($sceneNumber)->GetValue();

        if($this->IsDisplayVar($triggerIdent))
        {
            // load value stored in temp var
            $value = $triggerValue;
        }

        $this->DisplayVarBacking()->SetValue($value);
    }
}

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
        return new Variable($this->module, self::StrMS . "Lock");
    
    }

    private function LockSceneVars($sceneNumber)
    {
        return new Variable($this->module, 
            BetterBase::PersistentPrefix . 
            self::StrMS .
            self::StrScene . $sceneNumber . 
            "Lock");
    }

    // Idents

    private function MainSwitchTriggerIdent()
    {
        return self::StrMS . "MainSwitch" . "Trigger";
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
        $var->RegisterVariableBoolean("BM Sperren", "~Lock"); //, self::PosLightSwitch);
        $var->EnableAction();
    }

    private function RegisterSceneVars($sceneCount)
    {
        for($i = 0; $i<$sceneCount; $i++)
        {
            $var = $this->LockSceneVars($i);
            $var->RegisterVariableBoolean();
            $var->SetHidden(true);
        }
    }

    public function RegisterTriggers()
    {
        $this->module->RegisterTrigger($this->MainSwitchTriggerIdent(), $this->MainSwitchIdProp()->Value(), 'BL_MSMainSwitchEvent($_IPS[\'TARGET\']);', BetterBase::TriggerTypeUpdate);
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

class Scene
{
    private $index;
    private $module;

    const Size = 4;
    const StrScene = "Scene";

    public function __construct($module, $index) {
        $this->module = $module;
        $this->index = $index;
    }

    // Properties

    private function NameProp()
    {        
        return new PropertyString($this->module, self::StrScene . $this->index . "Name");
    }   

    private function ColorProp()
    {        
        return new PropertyString($this->module, self::StrScene . $this->index . "Color");
    }

    public function RegisterProperties()
    {
        $this->NameProp()->Register();
        $this->ColorProp()->Register();
    }

    public function Name()
    {
        return $this->NameProp()->Value();
    }

    public function SetName($value)
    {
        $this->NameProp()->SetValue($value);
    }

    public function Color()
    {
        return intval($this->ColorProp()->Value(), 0);
    }

    public function SetColor($value)
    {
        $this->ColorProp()->SetValue($value);
    }

    public function IsDefined()
    {
        return $this->Name() != "";
    }
}

?>