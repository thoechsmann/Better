<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Property.php");
require_once(__DIR__ . "/../Variable.php");
require_once(__DIR__ . "/../Backing.php");

class DimLight {
    const Prefix = "dimLight";

    private $index;
    private $module;

    public function __construct($module, $index) {
        $this->module = $module;
        $this->index = $index;
    }

    // Properties

    private function NameProp()
    {        
        return new PropertyString($this, self::Prefix . "Name" . $index);
    }   

    private function SwitchIdProp()
    {        
        return new PropertyInteger($this, self::Prefix . "SwitchId" . $index);
    }

    private function SetValueIdProp()
    {        
        return new PropertyInteger($this, self::Prefix . "SetValueId" . $index);
    }

    private function StatusValueIdProp()
    {        
        return new PropertyInteger($this, self::Prefix . "StatusValueId" . $index);
    }

    // Variables

    private function DisplayVar()
    {
        return new Variable($this, self::Prefix . "DisplayVar");
    }

    private function DisplayVarBacking()
    {
        $getterId = $this->StatusValueIdProp()->Value();
        $setterId = $this->SetValueIdProp()->Value();
        $displayIdent = $this->DisplayVar()->Ident();

        return new Backing($this, $displayIdent, $getterId, $setterId, Backing::EIBTypeScale);
    }
}

class BetterLight extends BetterBase {

    const MaxLights = 8;
    const MaxScenes = 4;
    const MaxSwitches = 4;

    const StrLight = "light";
    const StrScene = "scene";
    const StrSwitch = "switch";
    const StrDim = "Dim";
    const StrLink = "Link";

    const PosSceneSelection = 1;
    const PosMSLock = 2;
    const PosLightDim = 3;
    const PosLightSwitch = 4;
    const PosSaveSceneButton = 5;
    const PosSceneScheduler = 6;

    const SaveSceneIdent = "SaveScene";
    const MSMainSwitchTriggerIdent = "MSMainSwitchTrigger";
    const SceneSchedulerIdent = "SceneScheduler";
    const MSLockIdent = "MSLock";

    // Properties
    private function MSMainSwitchIdProperty()
    {
        return new PropertyInteger($this, "msMainSwitchId");
    }

    private function MSLockIdProperty()
    {
        return new PropertyInteger($this, "msLockId");
    }

    private function MSExternMovementIdProperty()
    {
        return new PropertyInteger($this, "msExternMovementId");
    }

    private function LightNameProperties()
    {        
        return new PropertyArrayString($this, self::StrLight . "Name", self::MaxLights);
    }

    private function LightSwitchIdProperties()
    {        
        return new PropertyArrayInteger($this, self::StrLight . "SwitchId", self::MaxLights);
    }

    private function LightDimIdProperties()
    {        
        return new PropertyArrayInteger($this, self::StrLight . "DimId", self::MaxLights);
    }

    private function LightStatusSwitchIdProperties()
    {        
        return new PropertyArrayInteger($this, self::StrLight . "StatusSwitchId", self::MaxLights);
    }

    private function LightStatusDimIdProperties()
    {        
        return new PropertyArrayInteger($this, self::StrLight . "StatusDimId", self::MaxLights);
    }

    private function SceneNameProperties()
    {        
        return new PropertyArrayString($this, self::StrScene . "Name", self::MaxScenes);
    }

    private function SceneColorProperties()
    {        
        return new PropertyArrayString($this, self::StrScene . "Color", self::MaxScenes);
    }

    private function SwitchIdProperties()
    {        
        return new PropertyArrayString($this, self::StrSwitch . "Id", self::MaxSwitches);
    }

    private function SwitchSceneProperties()
    {        
        return new PropertyArrayString($this, self::StrSwitch . "Scene", self::MaxSwitches);
    }

    // Variables
    private function LightSwitchVars()
    {
        return new VariableArray($this, self::StrLight . self::StrSwitch, self::MaxLights);
    }

    private function LightDimVars()
    {
        return new VariableArray($this, self::StrLight . self::StrDim, self::MaxLights);
    }

    private function MSLockVar()
    {
        return new Variable($this, "MSLock");
    }

    private function CurrentSceneVar()
    {
        return new Variable($this, parent::PersistentPrefix . "CurrentScene");
    }

    private function SaveToSceneVar()
    {
        return new Variable($this, "SaveToScene");
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


    private function SceneLightSwitchVars()
    {
        return new VariableArray($this, 
            parent::PersistentPrefix . self::StrScene . self::StrSwitch . self::StrLight, 
            self::MaxLights, 
            self::MaxScenes);
    }

    private function SceneLightDimVars()
    {
        return new VariableArray($this, 
            parent::PersistentPrefix . self::StrScene . self::StrDim . self::StrLight, 
            self::MaxLights, 
            self::MaxScenes);
    }

    private function SceneMSLockVars()
    {
        return new VariableArray($this, 
            parent::PersistentPrefix . self::StrScene . "MSLock", self::MaxScenes);
    }

    // Backing
    private function LightSwitchBacking($lightNumber)
    {
        $getterId = $this->LightStatusSwitchIdProperties()->ValueAt($lightNumber);
        $setterId = $this->LightSwitchIdProperties()->ValueAt($lightNumber);
        $displayIdent = $this->LightSwitchVars()->At($lightNumber)->Ident();

        return new Backing($this, $displayIdent, $getterId, $setterId, Backing::EIBTypeSwitch);
    }

    private function LightDimBacking($lightNumber)
    {
        $getterId = $this->LightStatusDimIdProperties()->ValueAt($lightNumber);
        $setterId = $this->LightDimIdProperties()->ValueAt($lightNumber);
        $displayIdent = $this->LightDimVars()->At($lightNumber)->Ident();

        return new Backing($this, $displayIdent, $getterId, $setterId, Backing::EIBTypeScale);
    }

    // Lights
    private function DimLight($lightNumber)
    {
        return new DimLight($lightNumer);
    }
    //

    private function SetSceneProfileString()
    {
        return "BL_setScenes_" . $this->GetName() . $this->InstanceID;
    }

    private function SaveSceneProfileString()
    {
        return "BL_saveScenes_" . $this->GetName() . $this->InstanceID;
    }

    private function LoadLightFromScene($lightNumber, $sceneNumber)
    {
        $switchId = $this->LightSwitchIdProperties()->ValueAt($lightNumber);
        $dimId = $this->LightDimIdProperties()->ValueAt($lightNumber);

        if($switchId == 0)
            return;

        if($dimId == 0)
        {
            $var = $this->SceneLightSwitchVars()->At($lightNumber, $sceneNumber);
            $backing = $this->LightSwitchBacking($lightNumber);
            $triggedValue = $this->IdendTriggerdTurnOnSwitchValueVar();
        }
        else
        {
            $var = $this->SceneLightDimVars()->At($lightNumber, $sceneNumber);
            $backing = $this->LightDimBacking($lightNumber);
            $triggedValue = $this->IdendTriggerdTurnOnDimValueVar();
        }

        $ident = $backing->DisplayIdent();
        $identTrigged = $this->IdendTriggerdTurnOnVar()->GetValue();

        if($ident == $identTrigged)
        {
            // load value stored in temp var
            $value = $triggedValue->GetValue();
        }
        else
        {
            // load value saved in scene 
            $value = $var->GetValue();
        }

        $backing->SetValue($value);
    }

    private function SaveLightToScene($lightNumber, $sceneNumber)
    {
        $switchId = $this->LightSwitchIdProperties()->ValueAt($lightNumber);
        $dimId = $this->LightDimIdProperties()->ValueAt($lightNumber);

        if($switchId == 0)
            return;

        if($dimId == 0)
        {
            $value = $this->LightSwitchBacking($lightNumber)->GetValue();
            $this->SceneLightSwitchVars()->At($lightNumber, $sceneNumber)->SetValue($value);
        }
        else
        {
            $value = $this->LightDimBacking($lightNumber)->GetValue();
            $this->SceneLightDimVars()->At($lightNumber, $sceneNumber)->SetValue($value);
        }
    }

    private function IsMSLocked()
    {
        $msId = $this->MSLockIdProperty()->Value();

        if($msId == 0)
            return false;

        return GetValue($msId);
    }

    private function SetMSLock($value)
    {
        $msId = $this->MSLockIdProperty()->Value();

        if($msId !== 0)
        {
            EIB_Switch(IPS_GetParent($msId), $value);
        }
    }

    private function LoadMSLockFromScene($sceneNumber)
    {
        // We just update the displayed var. 
        // It will not write it to EIB. That was already done in the SetScene method.
        $value = $this->SceneMSLockVars()->At($sceneNumber)->GetValue();
        $this->MSLockVar()->SetValue($value);
    }

    private function SaveMSLockToScene($sceneNumber)
    {
        $value = $this->MSLockVar()->GetValue();
        $this->SceneMSLockVars()->At($sceneNumber)->SetValue($value);
    }

    private function SetMSExternMovement()
    {
        $msId = $this->MSExternMovementIdProperty()->Value();

        if($msId !== 0)
        {
            EIB_Switch(IPS_GetParent($msId), true);
        }
    }

    public function MainSwitchStatus()
    {
        $msId = $this->MSMainSwitchIdProperty()->Value();
        return GetValue($msId);
    }

	public function Create() 
    {
		parent::Create();		
        
        $this->MSMainSwitchIdProperty()->Register();
        $this->MSLockIdProperty()->Register();
        $this->MSExternMovementIdProperty()->Register();

        // $this->LightNameProperties()->RegisterAll();
        // $this->LightSwitchIdProperties()->RegisterAll();
        // $this->LightDimIdProperties()->RegisterAll();
        // $this->LightStatusSwitchIdProperties()->RegisterAll();
        // $this->LightStatusDimIdProperties()->RegisterAll();

        $this->SwitchIdProperties()->RegisterAll();
        $this->SwitchSceneProperties()->RegisterAll();

        $this->SceneNameProperties()->RegisterAll();
        $this->SceneColorProperties()->RegisterAll();
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();
		
        // $this->CreateMotionTrigger();
        // $this->CreateLinks();
        // $this->CreateScenes();
        // $this->CreateSceneProfiles();
        // $this->CreateSceneSelectionVar();
        // $this->CreateSceneScheduler();
        // $this->AddSaveButton();        
	}

    private function CreateMotionTrigger()
    {
        $this->RegisterTrigger(self::MSMainSwitchTriggerIdent, $this->MSMainSwitchIdProperty()->Value(), 'BL_MSMainSwitchEvent($_IPS[\'TARGET\']);', self::TriggerTypeUpdate);
    }

    private function CreateLinks()
    {
        $this->IdendTriggerdTurnOnVar()->RegisterVariableString();
        $this->IdendTriggerdTurnOnVar()->SetValue("");
        $this->IdendTriggerdTurnOnVar()->SetHidden(true);

        $this->IdendTriggerdTurnOnSwitchValueVar()->RegisterVariableBoolean();
        $this->IdendTriggerdTurnOnSwitchValueVar()->SetHidden(true);

        $this->IdendTriggerdTurnOnDimValueVar()->RegisterVariableFloat();
        $this->IdendTriggerdTurnOnDimValueVar()->SetHidden(true);

        $this->CreateMSLink();

        for($i=0; $i<self::MaxLights; $i++)
        {
            $this->CreateLightLink($i);
        }
    }

    private function CreateMSLink()
    {
        $var = $this->MSLockVar();
        $var->RegisterVariableBoolean("BM Sperren", "~Lock", self::PosMSLock);
        $var->EnableAction();
    }

    private function CreateLightLink($lightNumber)
    {
        $switchId = $this->LightSwitchIdProperties()->ValueAt($lightNumber);
        $dimId = $this->LightDimIdProperties()->ValueAt($lightNumber);

        $name = $this->LightNameProperties()->ValueAt($lightNumber);        

        if($switchId != 0)
        {
            $switchBacking = $this->LightSwitchBacking($lightNumber);

            $lightSwitch = $this->LightSwitchVars()->At($lightNumber);
            $lightSwitch->RegisterVariableBoolean($name, "~Switch", self::PosLightSwitch);
            $lightSwitch->EnableAction();
            $lightSwitch->SetValue($switchBacking->GetValue());
            if($dimId != 0)
            {
                $lightSwitch->SetHidden(true);
            }

            $switchBacking->RegisterTrigger('BL_CancelSave($_IPS[\'TARGET\']);');
        }

        if($dimId != 0)
        {
            $dimBacking = $this->LightDimBacking($lightNumber);

            $lightDim = $this->LightDimVars()->At($lightNumber);
            $lightDim->RegisterVariableInteger($name, "~Intensity.100", self::PosLightSwitch);
            $lightDim->EnableAction();
            $lightDim->SetValue($dimBacking->GetValue());

            $dimBacking->RegisterTrigger('BL_CancelSave($_IPS[\'TARGET\']);');
        }
    }

    private function CreateScenes()
    {
        for($i = 0; $i < self::MaxScenes; $i++)
        {
            $this->CreateSceneVars($i);
        }
    }

    private function CreateSceneVars($sceneNumber)
    {
        if($sceneNumber == 0)
        {
            $this->SceneNameProperties()->At($sceneNumber)->SetValue("Aus");
            $this->SceneColorProperties()->At($sceneNumber)->SetValue("0x000000");            
        }

        $sceneName = $this->SceneNameProperties()->ValueAt($sceneNumber);
        
        if($sceneName === "")
            return;

        for($i=0; $i<self::MaxLights; $i++)
        {
            $this->CreateSceneLight($sceneNumber, $i);
        }

        $this->CreateMSLock($sceneNumber);
    }

    private function CreateSceneLight($sceneNumber, $lightNumber)
    {
        $switchId = $this->LightSwitchIdProperties()->ValueAt($lightNumber);
        $dimId = $this->LightDimIdProperties()->ValueAt($lightNumber);

        if($switchId != 0)
        {
            $name = $this->LightNameProperties()->ValueAt($lightNumber);
            
            if($dimId == 0)
            {
                $sceneLight = $this->SceneLightSwitchVars()->At($lightNumber, $sceneNumber);
                $sceneLight->RegisterVariableBoolean();
            }
            else
            {
                $sceneLight = $this->SceneLightDimVars()->At($lightNumber, $sceneNumber);
                $sceneLight->RegisterVariableInteger();
            }

            $sceneLight->EnableAction();
            $sceneLight->SetHidden(true);
        }
    }

    private function CreateMSLock($sceneNumber)
    {
        $var = $this->SceneMSLockVars()->At($sceneNumber);
        $var->RegisterVariableBoolean();
        $var->SetHidden(true);

        if($sceneNumber == 0)
        {
            $var->SetValue(true);
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
        
        for($sceneNumber = 0; $sceneNumber < self::MaxScenes; $sceneNumber++)
        {
            $sceneName = $this->SceneNameProperties()->ValueAt($sceneNumber);

            if($sceneName != "")
            {
                $sceneColor = intval($this->SceneColorProperties()->ValueAt($sceneNumber), 0);

                IPS_SetVariableProfileAssociation($setProfile, $sceneNumber, $sceneName, "", $sceneColor);

                if($sceneNumber != 0)
                {
                    IPS_SetVariableProfileAssociation($saveProfile, $sceneNumber, $sceneName, "", $sceneColor);
                }
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

        for($sceneNumber = 0; $sceneNumber<self::MaxScenes; $sceneNumber++)
        {
            $sceneName = $this->SceneNameProperties()->ValueAt($sceneNumber);
            
            if($sceneName != "")
            {
                $sceneColor = intval($this->SceneColorProperties()->ValueAt($sceneNumber), 0);

                IPS_SetEventScheduleAction($schedulerId, $sceneNumber, $sceneName, $sceneColor, 
                    "BL_SetScene(\$_IPS['TARGET'], $sceneNumber);");
            }
        }
    }

    private function AddSaveButton() 
    {
        $saveToScene = $this->SaveToSceneVar();
        $saveToScene->RegisterVariableInteger("Speichern unter:", $this->SaveSceneProfileString(), self::PosSaveSceneButton);
        $saveToScene->EnableAction();
        $saveToScene->SetValue(-1);

        $this->RegisterScript(self::SaveSceneIdent, "Szene speichern", 
            "<? BL_StartSave(" . $this->InstanceID . ");?>",
            self::PosSaveSceneButton);

        $this->CancelSave();
    }

    public function StartSave()
    {
        $this->SaveToSceneVar()->SetHidden(false);

        $id = $this->GetIDForIdent(self::SaveSceneIdent);
        IPS_SetHidden($id, true);        
    }

    private function SaveToScene($sceneNumber)
    {
        for($lightNumber = 0; $lightNumber < self::MaxLights; $lightNumber++)
        {
            $this->SaveLightToScene($lightNumber, $sceneNumber);
        }
        $this->SaveMSLockToScene($sceneNumber);

        $this->CancelSave();
    }

    private function LoadFromScene($sceneNumber)
    {
        IPS_LogMessage("BL","LoadFromScene(sceneNumber = $sceneNumber) ");

        for($lightNumber = 0; $lightNumber < self::MaxLights; $lightNumber++)
        {
            $this->LoadLightFromScene($lightNumber, $sceneNumber);
        }            
    }

    public function SetScene($sceneNumber, $turnOn = false)
    {
        IPS_LogMessage("BL","SetScene(sceneNumber = $sceneNumber, turnOn = $turnOn) ");
        $this->CurrentSceneVar()->SetValue($sceneNumber);
        $this->CancelSave();
        $isOn = $this->MainSwitchStatus();
        $isMSLocked = $this->IsMSLocked();
        $shouldBeLocked = $this->SceneMSLockVars()->At($sceneNumber)->GetValue();

        if($isOn)
        {
            if($isMSLocked != $shouldBeLocked)
                $this->SetMSLock($shouldBeLocked);

            // Do not load scene when ms is activated nad light is on as turning ms lock on will send light status event.
            // This event will be catched and used to set the current scene.
            if(!$shouldBeLocked)
                $this->LoadFromScene($sceneNumber);

        }
        else if($turnOn)
        {
            if($isMSLocked != $shouldBeLocked)
                $this->SetMSLock($shouldBeLocked);

            if($shouldBeLocked)
                $this->LoadFromScene($sceneNumber);

            $this->SetMSExternMovement();
        }
    }

    public function CancelSave()
    {
        $this->SaveToSceneVar()->SetHidden(true);

        $id = $this->GetIDForIdent(self::SaveSceneIdent);
        IPS_SetHidden($id, false);        
    }

    // FIX: Remove storeVar. Save everything in a string.
    private function SetBackedValue($backing, $value, $storeVar)
    {
        $this->CancelSave();            
        $isOn = $this->MainSwitchStatus();
        $IsMSLocked = $this->IsMSLocked();

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
                $this->SetMSExternMovement();
            }

            $backing->SetValue($value);
        }
    }

    public function RequestAction($ident, $value) 
    {
        $lightNumber = $this->LightSwitchVars()->GetIndexForIdent($ident);
        if($lightNumber !== false)
        {
            $this->SetBackedValue(
                $this->LightSwitchBacking($lightNumber), 
                $value, 
                $this->IdendTriggerdTurnOnSwitchValueVar());

            return;
        }

        $lightNumber = $this->LightDimVars()->GetIndexForIdent($ident);
        if($lightNumber !== false)
        {
            $this->SetBackedValue(
                $this->LightDimBacking($lightNumber), 
                $value, 
                $this->IdendTriggerdTurnOnDimValueVar());

            return;
        }

        switch($ident) {
            case self::SaveSceneIdent:
                $this->StartSave();
                break;

            case $this->MSLockVar()->Ident():
                $this->SetValueForIdent($ident, $value);
                $this->CancelSave();
                break;

            case $this->CurrentSceneVar()->Ident():
                $this->SetScene($value, true);
                break;

            case $this->SaveToSceneVar()->Ident():
                $this->SaveToScene($value);
                break;

            default:
                $this->SetValueForIdent($ident, $value);
                $this->CancelSave();
        }
    }

    public function MSMainSwitchEvent()
    {
        $turnOn = $this->MainSwitchStatus();

        IPS_LogMessage("BL", "MSMainSwitchEvent - turnOn:$turnOn, isMSLocked:" . $this->IsMSLocked());

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
        for($lightNumber = 0; $lightNumber < self::MaxLights; $lightNumber++)
        {
            if($this->LightSwitchIdProperties()->ValueAt($lightNumber) != 0)
                $this->LightSwitchBacking($lightNumber)->SetValue(false);
        }
    }

}
?>