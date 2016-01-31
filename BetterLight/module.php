<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Property.php");
require_once(__DIR__ . "/../Variable.php");
require_once(__DIR__ . "/../Backing.php");

class DimLight {
    const Size = 6;
    const StrLight = "DimLight";
    const StrScene = "Scene";

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

    private $index;
    private $module;

    public function __construct($module, $index) {
        $this->module = $module;
        $this->index = $index;
    }

    // Properties

    private function NameProp()
    {        
        return new PropertyString($this->module, self::StrLight . $this->index . "Name");
    }   

    private function SwitchIdProp()
    {        
        return new PropertyInteger($this->module, self::StrLight . $this->index . "SwitchId");
    }

    private function SetValueIdProp()
    {        
        return new PropertyInteger($this->module, self::StrLight . $this->index . "SetValueId");
    }

    private function StatusValueIdProp()
    {        
        return new PropertyInteger($this->module, self::StrLight . $this->index . "StatusValueId");
    }

    // Variables

    private function DisplayVar()
    {
        return new Variable($this->module, 
            self::StrLight . $this->index .
            "DisplayVar");
    }

    public function IsDisplayVar($ident)
    {
        return $ident == $this->DisplayVar()->Ident();
    }

    private function SceneVars($sceneNumber)
    {
        return new Variable($this->module, 
            BetterBase::PersistentPrefix . 
            self::StrLight . $this->index . 
            self::StrScene . $sceneNumber . 
            "Value");
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
        $this->NameProp()->Register();
        $this->SwitchIdProp()->Register();
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

    public function Name()
    {
        return $this->NameProp()->Value();
    }

    public function IsDefined()
    {
        return $this->Name() != "";
    }

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

class BetterLight extends BetterBase {

    const MaxSwitches = 4;

    const StrSwitch = "switch";

    const PosSceneSelection = 1;
    const PosMSLock = 2;
    const PosLightDim = 3;
    const PosLightSwitch = 4;
    const PosSaveSceneButton = 5;
    const PosSceneScheduler = 6;

    const SaveSceneStartIdent = "SaveSceneStart";
    const SaveSceneSelectIdent = "SaveSceneSelect";
    const SceneSchedulerIdent = "SceneScheduler";

    // Properties

    private function SwitchIdProperties()
    {        
        return new PropertyArrayString($this, self::StrSwitch . "Id", self::MaxSwitches);
    }

    private function SwitchSceneProperties()
    {        
        return new PropertyArrayString($this, self::StrSwitch . "Scene", self::MaxSwitches);
    }

    // Variables

    private function CurrentSceneVar()
    {
        return new Variable($this, parent::PersistentPrefix . "CurrentScene");
    }

    private function SaveToSceneVar()
    {
        return new Variable($this, self::SaveSceneSelectIdent);
    }

    private function IdendTriggerdTurnOnVar()
    {
        return new Variable($this, "IdendTriggerdTurnOn");
    }

    private function IdendTriggerdTurnOnSwitchValueVar()
    {
        return new Variable($this, "IdendTriggerdTurnOnSwitchValue");
    }

    private function IdendTriggerdTurnOnDimValueVar()
    {
        return new Variable($this, "IdendTriggerdTurnOnDimValue");
    }


    //
    private function DimLights($lightNumber)
    {
        return new DimLight($this, $lightNumber);
    }

    private function Scenes($sceneNumber)
    {
        return new Scene($this, $sceneNumber);
    }

    private function MotionSensor()
    {
        return new MotionSensor($this);
    }
    //


    private function DimLightCount()
    {
        $count = 0;
        
        for($i=0; $i<DimLight::Size; $i++)
        {
            $light = $this->DimLights($i);

            if(!$light->IsDefined())
            {
                return $count;
            }

            $count++;
        }
    }

    private function SceneCount()
    {
        $count = 0;
        
        for($i=0; $i<Scene::Size; $i++)
        {
            $scene = $this->Scenes($i);

            if(!$scene->IsDefined())
            {
                return $count;
            }

            $count++;
        }
    }

    private function SetSceneProfileString()
    {
        return "BL_setScenes_" . $this->GetName() . $this->InstanceID;
    }

    private function SaveSceneProfileString()
    {
        return "BL_saveScenes_" . $this->GetName() . $this->InstanceID;
    }

    public function Create() 
    {
        parent::Create();       

        $this->MotionSensor()->RegisterProperties();

        for($i=0; $i<DimLight::Size; $i++)
        {
            $this->DimLights($i)->RegisterProperties();
        }

        for($i=0; $i<Scene::Size; $i++)
        {
            $this->Scenes($i)->RegisterProperties();
        }

        $this->SwitchIdProperties()->RegisterAll();
        $this->SwitchSceneProperties()->RegisterAll();

        // Set default values
        $this->Scenes(0)->SetName("Aus");
        $this->Scenes(0)->SetColor("0x000000");

        $this->Scenes(1)->SetName("Standard");
        $this->Scenes(1)->SetColor("0x00FF00");
    }
    
    public function ApplyChanges() 
    {
        parent::ApplyChanges();
        
        $this->CreateMotionSensor();
        $this->CreateLights();
        $this->CreateSceneProfiles();
        $this->CreateSceneSelectionVar();
        $this->CreateSceneScheduler();
        $this->CreateSaveButton();        

        $this->IdendTriggerdTurnOnVar()->RegisterVariableString();
        $this->IdendTriggerdTurnOnVar()->SetValue("");
        $this->IdendTriggerdTurnOnVar()->SetHidden(true);

        $this->IdendTriggerdTurnOnSwitchValueVar()->RegisterVariableBoolean();
        $this->IdendTriggerdTurnOnSwitchValueVar()->SetHidden(true);

        $this->IdendTriggerdTurnOnDimValueVar()->RegisterVariableFloat();
        $this->IdendTriggerdTurnOnDimValueVar()->SetHidden(true);

        // Set defaults
        $this->MotionSensor()->SetSceneLock(0, true);
        $this->MotionSensor()->SetSceneLock(1, false);

    }

    private function CreateMotionSensor()
    {
        $ms = $this->MotionSensor();
        $ms->RegisterVariables($this->SceneCount());
        $ms->RegisterTriggers();

    }

    private function CreateLights()
    {
        $sceneCount = $this->SceneCount();

        for($i=0; $i<$this->DimLightCount(); $i++)
        {
            $light = $this->DimLights($i);

            IPS_LogMessage("BL", "Registering Light $i");
            $light->RegisterVariables($sceneCount);
            $light->RegisterTriggers();      
        }
    }

    private function CreateSceneProfiles()
    {   
        $setProfile = $this->SetSceneProfileString();
        $saveProfile = $this->SaveSceneProfileString();

        @IPS_DeleteVariableProfile($setProfile);
        IPS_CreateVariableProfile($setProfile, 1);

        @IPS_DeleteVariableProfile($saveProfile);
        IPS_CreateVariableProfile($saveProfile, 1);
        
        for($sceneNumber = 0; $sceneNumber < $this->SceneCount(); $sceneNumber++)
        {
            $scene = $this->Scenes($sceneNumber);

            IPS_SetVariableProfileAssociation($setProfile, $sceneNumber, $scene->Name(), "", $scene->Color());

            if($sceneNumber != 0)
            {
                IPS_SetVariableProfileAssociation($saveProfile, $sceneNumber, $scene->Name(), "", $scene->Color());
            }
        }
    }

    private function CreateSceneSelectionVar() 
    {
        $currentScene = $this->CurrentSceneVar();
        $currentScene->RegisterVariableInteger("Szene", $this->SetSceneProfileString());
        $currentScene->EnableAction();
        $currentScene->SetPosition(self::PosSceneSelection);
    }

    private function CreateSceneScheduler()
    {
        // Scheduled Event
        $schedulerId = $this->RegisterScheduler(parent::PersistentPrefix . self::SceneSchedulerIdent, "Szenen Zeiten");
        IPS_SetIcon($schedulerId, "Calendar");
        IPS_SetHidden($schedulerId, false);
        IPS_SetPosition($schedulerId, self::PosSceneScheduler);
        IPS_SetEventScheduleGroup($schedulerId, 0, 127); //Mo - Fr (1 + 2 + 4 + 8 + 16)

        for($sceneNumber = 0; $sceneNumber<$this->SceneCount(); $sceneNumber++)
        {
            $scene = $this->Scenes($sceneNumber);
            
            IPS_SetEventScheduleAction($schedulerId, $sceneNumber, $scene->Name(), $scene->Color(), 
                "BL_SetScene(\$_IPS['TARGET'], $sceneNumber);");
        }
    }

    private function CreateSaveButton() 
    {
        $saveToScene = $this->SaveToSceneVar();
        $saveToScene->RegisterVariableInteger("Speichern unter:", $this->SaveSceneProfileString(), self::PosSaveSceneButton);
        $saveToScene->EnableAction();
        $saveToScene->SetValue(-1);

        $id = $this->RegisterScript(self::SaveSceneStartIdent, "Szene speichern", 
            "<? BL_StartSave(" . $this->InstanceID . ");?>",
            self::PosSaveSceneButton);

        $this->CancelSave();
    }

    public function StartSave()
    {
        IPS_LogMessage("BL","StartSave() ");
        $this->SaveToSceneVar()->SetHidden(false);

        $id = $this->GetIDForIdent(self::SaveSceneStartIdent);
        IPS_SetHidden($id, true);        
    }

    private function SaveToScene($sceneNumber)
    {
        IPS_LogMessage("BL","SaveToScene(sceneNumber = $sceneNumber) ");

        for($i = 0; $i < $this->DimLightCount(); $i++)
        {
            $this->DimLights($i)->SaveToScene($sceneNumber);
        }
        
        $this->MotionSensor()->SaveToScene($sceneNumber);

        $this->CancelSave();
    }

    private function LoadFromScene($sceneNumber)
    {
        IPS_LogMessage("BL","LoadFromScene(sceneNumber = $sceneNumber) ");

        $triggerIdent = $this->IdendTriggerdTurnOnVar()->GetValue();
        $triggerBoolValue = $this->IdendTriggerdTurnOnDimValueVar()->GetValue();

        for($i = 0; $i < $this->DimLightCount(); $i++)
        {
            $this->DimLights($i)->LoadFromScene($sceneNumber, $triggerIdent, $triggerBoolValue);
        }

        // Motion Sensor is set in SetScene.
    }

    public function SetScene($sceneNumber, $turnOn = false)
    {
        IPS_LogMessage("BL","SetScene(sceneNumber = $sceneNumber, turnOn = $turnOn) ");
        $this->CurrentSceneVar()->SetValue($sceneNumber);
        $this->CancelSave();
 
        $ms = $this->MotionSensor();
        $isOn = $ms->IsMainSwitchOn();

        if($isOn || $turnOn)
        {
            $ms->LoadFromScene($sceneNumber);
        }

        if($isOn)
        {
            // Do not load scene when ms is activated and light is on as turning ms lock on will send light status event.
            // This event will be catched and used to set the current scene.
            if(!$ms->IsLocked())
                $this->LoadFromScene($sceneNumber);

        }
        else if($turnOn)
        {
            if($ms->IsLocked())
                $this->LoadFromScene($sceneNumber);
            else
                $ms->TriggerExternMovement();
        }
    }

    public function CancelSave()
    {
        IPS_LogMessage("BL","CancelSave() ");
        $this->SaveToSceneVar()->SetHidden(true);

        $id = $this->GetIDForIdent(self::SaveSceneStartIdent);
        IPS_SetHidden($id, false);        
    }

    // FIX: Remove storeVar. Save everything in a string.
    private function SetBackedValue($backing, $value, $storeVar)
    {
        $this->CancelSave();            

        $ms = $this->MotionSensor();
        $isOn = $ms->IsMainSwitchOn();
        $IsMSLocked = $ms->IsLocked();

        if($IsMSLocked)
        {
            // if MS is locked we do not get a turn on event.
            $backing->SetValue($value);
            $this->SetScene($this->CurrentSceneVar()->GetValue());
        }
        else
        {
            if(!$isOn)
            {
                $this->IdendTriggerdTurnOnVar()->SetValue($backing->DisplayIdent());
                $storeVar->SetValue($value);
                $ms->TriggerExternMovement();
            }

            $backing->SetValue($value);
        }
    }

    public function RequestAction($ident, $value) 
    {
        IPS_LogMessage("BL", "RequestAction - ident:$ident, value:$value");
        // $lightNumber = $this->LightSwitchVars()->GetIndexForIdent($ident);
        // if($lightNumber !== false)
        // {
        //     $this->SetBackedValue(
        //         $this->LightSwitchBacking($lightNumber), 
        //         $value, 
        //         $this->IdendTriggerdTurnOnSwitchValueVar());

        //     return;
        // }

        $lightNumber = DimLight::GetIndexForDisplayIdent($ident);
        if($lightNumber !== false)
        {
            IPS_LogMessage("BL", "RequestAction DimLight - ident:$ident, value:$value");

            $this->SetBackedValue(
                $this->DimLights($lightNumber)->DisplayVarBacking(), 
                $value, 
                $this->IdendTriggerdTurnOnDimValueVar());

            return;
        }

        switch($ident) {
            case self::SaveSceneStartIdent:
                $this->StartSave();
                break;

            case $this->CurrentSceneVar()->Ident():
                $this->SetScene($value, true);
                break;

            case $this->SaveToSceneVar()->Ident():
                IPS_LogMessage("BL", "RequestAction SaveToSceneVar - ident:$ident, value:$value");
                $this->SaveToScene($value);
                break;

            default:
                IPS_LogMessage("BL", "RequestAction default - ident:$ident, value:$value");
                $this->SetValueForIdent($ident, $value);
                $this->CancelSave();
        }
    }

    public function MSMainSwitchEvent()
    {
        $ms = $this->MotionSensor();

        $turnOn = $ms->IsMainSwitchOn();

        IPS_LogMessage("BL", "MSMainSwitchEvent - turnOn:$turnOn, isMSLocked:" . $ms->IsLocked());

        if($turnOn)
        {
            $this->LoadFromScene($this->CurrentSceneVar()->GetValue());
        }
        else
        {
            $this->TurnOffAll();
        }

        $this->IdendTriggerdTurnOnVar()->SetValue("");
    }

    public function TurnOffAll()
    {
        // for($lightNumber = 0; $lightNumber < self::MaxLights; $lightNumber++)
        // {
        //     if($this->LightSwitchIdProperties()->ValueAt($lightNumber) != 0)
        //         $this->LightSwitchBacking($lightNumber)->SetValue(false);
        // }
    }

}
?>