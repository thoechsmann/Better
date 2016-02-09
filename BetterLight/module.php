<?
require_once(__DIR__ . "/Light.php");
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Property.php");
require_once(__DIR__ . "/../Variable.php");
require_once(__DIR__ . "/../Backing.php");


class BetterLight extends BetterBase {

    const MaxSwitches = 4;

    const StrSwitch = "switch";

    const PosSceneSelection = 1;
    const PosMSLock = 2;
    const PosLightDim = 3;
    const PosLightSwitch = 4;
    const PosSaveSceneButton = 5;
    const PosSceneScheduler = 6;

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
        return new IPSVarInteger($this->InstanceID, parent::PersistentPrefix . "CurrentScene");
    }

    private function SaveToSceneVar()
    {
        return new IPSVarInteger($this->InstanceID, "SaveSceneSelect");
    }

    private function IdendTriggerdTurnOnVar()
    {
        return new IPSVarInteger($this->InstanceID, "IdendTriggerdTurnOn");
    }

    private function IdendTriggerdTurnOnBooelanValueVar()
    {
        return new IPSVarBoolean($this->InstanceID, "IdendTriggerdTurnOnBooelanValue");
    }

    private function IdendTriggerdTurnOnIntegerValueVar()
    {
        return new IPSVarInteger($this->InstanceID, "IdendTriggerdTurnOnIntegerValue");
    }

    private function IdendTriggerdTurnOnFloatValueVar()
    {
        return new IPSVarFloat($this->InstanceID, "IdendTriggerdTurnOnFloatValue");
    }

    // Scripts

    private function SaveSceneScript()
    {
        return new IPSScript($this->InstanceID, "SaveSceneStart");
    }

    // Actions

    private function SceneScheduler()
    {
        return new IPSEventScheduler($this->InstanceID, parent::PersistentPrefix . "SceneScheduler");
    }


    //
    private function DimLights($lightNumber)
    {
        return new DimLight($this, $lightNumber);
    }

    private function SwitchLights($lightNumber)
    {
        return new SwitchLight($this, $lightNumber);
    }

    private function RGBLights($lightNumber)
    {
        return new RGBLight($this, $lightNumber);
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

    private function SwitchLightCount()
    {
        $count = 0;
        
        for($i=0; $i<SwitchLight::Size; $i++)
        {
            $light = $this->SwitchLights($i);

            if(!$light->IsDefined())
            {
                return $count;
            }

            $count++;
        }
    }

    private function RGBLightCount()
    {
        $count = 0;
        
        for($i=0; $i<RGBLight::Size; $i++)
        {
            $light = $this->RGBLights($i);

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

        for($i=0; $i<SwitchLight::Size; $i++)
        {
            $this->SwitchLights($i)->RegisterProperties();
        }

        for($i=0; $i<RGBLight::Size; $i++)
        {
            $this->RGBLights($i)->RegisterProperties();
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

        $this->IdendTriggerdTurnOnVar()->Register();
        $this->IdendTriggerdTurnOnVar()->SetValue("");
        $this->IdendTriggerdTurnOnVar()->SetHidden(true);

        $this->IdendTriggerdTurnOnBooelanValueVar()->Register();
        $this->IdendTriggerdTurnOnBooelanValueVar()->SetHidden(true);

        $this->IdendTriggerdTurnOnFloatValueVar()->Register();
        $this->IdendTriggerdTurnOnFloatValueVar()->SetHidden(true);

        $this->IdendTriggerdTurnOnIntegerValueVar()->Register();
        $this->IdendTriggerdTurnOnIntegerValueVar()->SetHidden(true);

        // Set defaults
        $this->MotionSensor()->SetSceneLock(0, 1);
        $this->MotionSensor()->SetSceneLock(1, 0);

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

        for($i=0; $i<$this->DimLightCount();  $i++)
        {
            $light = $this->DimLights($i);

            IPS_LogMessage("BL", "Registering vars for Dim Light $i");
            $light->RegisterVariables($sceneCount);
            $light->RegisterTriggers();      
        }

        for($i=0; $i<$this->SwitchLightCount(); $i++)
        {
            $light = $this->SwitchLights($i);

            IPS_LogMessage("BL", "Registering vars for Switch Light $i");
            $light->RegisterVariables($sceneCount);
            $light->RegisterTriggers();      
        }

        for($i=0; $i<$this->RGBLightCount(); $i++)
        {
            $light = $this->RGBLights($i);

            IPS_LogMessage("BL", "Registering vars for RGB Light $i");
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
        $currentScene->Register("Szene", $this->SetSceneProfileString());
        $currentScene->EnableAction();
        $currentScene->SetPosition(self::PosSceneSelection);
    }

    private function CreateSceneScheduler()
    {
        // Scheduled Event
        $scheduler = $this->SceneScheduler();
        $scheduler->Register("Szenen Zeiten", self::PosSceneScheduler);
        $scheduler->SetIcon("Calendar");
        $scheduler->SetHidden(false);
        $scheduler->SetGroup(0, 127); //Mo - Fr (1 + 2 + 4 + 8 + 16)

        for($sceneNumber = 0; $sceneNumber<$this->SceneCount(); $sceneNumber++)
        {
            $scene = $this->Scenes($sceneNumber);
            
            $scheduler->SetAction($sceneNumber, $scene->Name(), $scene->Color(), 
                "BL_SetScene(\$_IPS['TARGET'], $sceneNumber);");
        }
    }

    private function CreateSaveButton() 
    {
        $saveToScene = $this->SaveToSceneVar();
        $saveToScene->Register("Speichern unter:", $this->SaveSceneProfileString(), self::PosSaveSceneButton);
        $saveToScene->EnableAction();
        $saveToScene->SetValue(-1);

        $this->SaveSceneScript()->Register("Szene speichern", "<? BL_StartSave(" . $this->InstanceID . ");?>", self::PosSaveSceneButton);

        $this->CancelSave();
    }

    public function StartSave()
    {
        IPS_LogMessage("BL","StartSave() ");
        $this->SaveToSceneVar()->SetHidden(false);
        $this->SaveSceneScript()->SetHidden(true);
    }

    private function SaveToScene($sceneNumber)
    {
        IPS_LogMessage("BL","SaveToScene(sceneNumber = $sceneNumber)");

        for($i = 0; $i < $this->DimLightCount(); $i++)
        {
            $this->DimLights($i)->SaveToScene($sceneNumber);
        }

        for($i = 0; $i < $this->SwitchLightCount(); $i++)
        {
            $this->SwitchLights($i)->SaveToScene($sceneNumber);
        }

        for($i = 0; $i < $this->RGBLightCount(); $i++)
        {
            $this->RGBLights($i)->SaveToScene($sceneNumber);
        }
        
        $this->MotionSensor()->SaveToScene($sceneNumber);

        $this->CancelSave();
    }

    private function LoadFromScene($sceneNumber)
    {
        IPS_LogMessage("BL","LoadFromScene(sceneNumber = $sceneNumber)");

        $triggerIdent = $this->IdendTriggerdTurnOnVar()->GetValue();
        $triggerBoolValue = $this->IdendTriggerdTurnOnFloatValueVar()->GetValue();

        for($i = 0; $i < $this->DimLightCount(); $i++)
        {
            $this->DimLights($i)->LoadFromScene($sceneNumber, $triggerIdent, $triggerBoolValue);
        }

        for($i = 0; $i < $this->SwitchLightCount(); $i++)
        {
            $this->SwitchLights($i)->LoadFromScene($sceneNumber, $triggerIdent, $triggerBoolValue);
        }

        for($i = 0; $i < $this->RGBLightCount(); $i++)
        {
            $this->RGBLights($i)->LoadFromScene($sceneNumber, $triggerIdent, $triggerBoolValue);
        }

        // Motion Sensor is set in SetScene.
    }

    public function SetScene($sceneNumber, $turnOn = false)
    {
        IPS_LogMessage("BL","SetScene(sceneNumber = $sceneNumber, turnOn = $turnOn)");
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
        $this->SaveSceneScript()->SetHidden(false);
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

        $lightNumber = SwitchLight::GetIndexForDisplayIdent($ident);
        if($lightNumber !== false)
        {
            IPS_LogMessage("BL", "RequestAction SwitchLight - ident:$ident, value:$value");

            $this->SetBackedValue(
                $this->SwitchLights($lightNumber)->DisplayVarBacking(), 
                $value, 
                $this->IdendTriggerdTurnOnBooelanValueVar());

            return;
        }

        $lightNumber = DimLight::GetIndexForDisplayIdent($ident);
        if($lightNumber !== false)
        {
            IPS_LogMessage("BL", "RequestAction DimLight - ident:$ident, value:$value");

            $this->SetBackedValue(
                $this->DimLights($lightNumber)->DisplayVarBacking(), 
                $value, 
                $this->IdendTriggerdTurnOnFloatValueVar());

            return;
        }

        $lightNumber = RGBLight::GetIndexForDisplayIdent($ident);
        if($lightNumber !== false)
        {
            IPS_LogMessage("BL", "RequestAction RGBLight - ident:$ident, value:$value");

            $this->SetBackedValue(
                $this->RGBLights($lightNumber)->DisplayVarBacking(), 
                $value, 
                $this->IdendTriggerdTurnOnIntegerValueVar());

            return;
        }

        switch($ident) {
            case $this->SaveSceneScript()->Ident():
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
        for($i = 0; $i < $this->DimLightCount(); $i++)
        {
            $this->DimLights($i)->TurnOff();
        }

        for($i = 0; $i < $this->SwitchLightCount(); $i++)
        {
            $this->SwitchLights($i)->TurnOff();
        }

        for($i = 0; $i < $this->RGBLightCount(); $i++)
        {
            $this->RGBLights($i)->TurnOff();
        }

    }

}
?>