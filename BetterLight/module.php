<?
require_once(__DIR__ . "/Light.php");
require_once(__DIR__ . "/Scene.php");
require_once(__DIR__ . "/SceneSwitch.php");
require_once(__DIR__ . "/MotionSensor.php");
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../Backing.php");

require_once(__DIR__ . "/../IPS/IPS.php");

class BetterLight extends BetterBase
{
    const MaxDimLights = 6;
    const MaxSwitchLights = 2;
    const MaxRGBLights = 2;
    const MaxSceneSwitches = 8;
    const MaxScenes = 4;

    const StrSwitch = "switch";

    const PosSceneSelection = 1;
    const PosMSLock = 2;
    const PosLightDim = 3;
    const PosLightSwitch = 4;
    const PosLightRGB = 5;
    const PosSaveSceneButton = 6;
    const PosSceneScheduler = 7;
    const PosTurnOffButton = 8;

    const OffSceneNumber = 0;
    const DefaultSceneNumber = 1;

    const OffTimerTime = 20;

    protected function GetModuleName()
    {
        return "BL";
    }

    // Variables
    private function CurrentSceneVar()
    {
        return new IPSVarInteger($this->InstanceID(), parent::PersistentPrefix . "CurrentScene");
    }

    private function SaveToSceneVar()
    {
        return new IPSVarInteger($this->InstanceID(), "SaveSceneSelect");
    }

    private function IdendTriggerdTurnOnVar()
    {
        return new IPSVarString($this->InstanceID(), "IdendTriggerdTurnOn");
    }

    private function IdendTriggerdTurnOnValueVar()
    {
        return new IPSVarString($this->InstanceID(), "IdendTriggerdTurnOnValue");
    }

    // Scripts
    private function SaveSceneScript()
    {
        return new IPSScript($this->InstanceID(), "SaveSceneStart");
    }

    private function TurnOffScript()
    {
        return new IPSScript($this->InstanceID(), "TurnOff");
    }

    private function AlexaScript()
    {
        return new IPSScript($this->InstanceID(), "Alexa");
    }

    // Events
    private function SceneScheduler()
    {
        return new IPSEventScheduler($this->InstanceID(), parent::PersistentPrefix . "SceneScheduler");
    }

    private function OffTimer()
    {
        return new IPSEventCyclic($this->InstanceID(), "OffTimer");
    }

    // Lights
    private function DimLights()
    {
        return new LightArray($this, self::MaxDimLights, LightArray::TypeDim);
    }

    private function SwitchLights()
    {
        return new LightArray($this, self::MaxSwitchLights, LightArray::TypeSwitch);
    }

    private function RGBLights()
    {
        return new LightArray($this, self::MaxRGBLights, LightArray::TypeRGB);
    }

    private function Scenes()
    {
        return new SceneArray($this, self::MaxScenes);
    }

    private function SceneSwitches()
    {
        return new SceneSwitchArray($this, self::MaxSceneSwitches);
    }

    private function MotionSensor()
    {
        return new MotionSensor($this);
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

    public function Create()
    {
        parent::Create();

        $this->MotionSensor()->RegisterProperties();

        $this->DimLights()->RegisterProperties();
        $this->SwitchLights()->RegisterProperties();
        $this->RGBLights()->RegisterProperties();

        $this->Scenes()->RegisterProperties();
        $this->SceneSwitches()->RegisterProperties();

        // Set default values
        $this->Scenes()->At(self::OffSceneNumber)->SetName("Aus");
        $this->Scenes()->At(self::OffSceneNumber)->SetColor("0x000000");

        $this->Scenes()->At(self::DefaultSceneNumber)->SetName("Standard");
        $this->Scenes()->At(self::DefaultSceneNumber)->SetColor("0x00FF00");
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->CreateMotionSensor();
        $this->CreateLights();
        $this->CreateSceneProfiles();
        $this->CreateSceneSelectionVar();
        $this->CreateSceneScheduler();
        $this->CreateSceneSwitches();
        $this->CreateSaveButton();
        $this->CreateTurnOffButton();
        $this->CreateAlexaScript();

        $this->IdendTriggerdTurnOnVar()->Register();
        $this->IdendTriggerdTurnOnVar()->SetValue("");
        $this->IdendTriggerdTurnOnVar()->Hide();

        $this->IdendTriggerdTurnOnValueVar()->Register();
        $this->IdendTriggerdTurnOnValueVar()->Hide();

        // Set defaults
        $this->MotionSensor()->SetSceneLock(self::OffSceneNumber, MotionSensor::StateAlwaysOff);
        $this->MotionSensor()->SetSceneLock(self::DefaultSceneNumber, MotionSensor::StateAuto);
    }

    private function CreateMotionSensor()
    {
        $ms = $this->MotionSensor();
        $ms->RegisterVariables($this->Scenes()->Count(), self::PosMSLock);
        $ms->RegisterTriggers();
    }

    private function CreateLights()
    {
        $sceneCount = $this->Scenes()->Count();

        $this->dimLights()->RegisterVariables($sceneCount, self::PosLightDim);
        $this->SwitchLights()->RegisterVariables($sceneCount, self::PosLightSwitch);
        $this->RGBLights()->RegisterVariables($sceneCount, self::PosLightRGB);

        $this->dimLights()->RegisterTriggers();
        $this->SwitchLights()->RegisterTriggers();
        $this->RGBLights()->RegisterTriggers();
    }

    private function CreateSceneProfiles()
    {
        $setProfile = $this->SetSceneProfileString();
        $saveProfile = $this->SaveSceneProfileString();

        @IPS_DeleteVariableProfile($setProfile);
        IPS_CreateVariableProfile($setProfile, 1);

        @IPS_DeleteVariableProfile($saveProfile);
        IPS_CreateVariableProfile($saveProfile, 1);

        for ($sceneNumber = 0; $sceneNumber < $this->Scenes()->Count(); $sceneNumber++) {
            $scene = $this->Scenes()->At($sceneNumber);

            IPS_SetVariableProfileAssociation($setProfile, $sceneNumber, $scene->Name(), "", $scene->Color());

            if ($sceneNumber != 0) {
                IPS_SetVariableProfileAssociation($saveProfile, $sceneNumber, $scene->Name(), "", $scene->Color());
            }
        }
    }

    private function CreateSceneSelectionVar()
    {
        $currentScene = $this->CurrentSceneVar();
        $currentScene->Register("Szene", $this->SetSceneProfileString(), self::PosSceneSelection);
        $currentScene->EnableAction();
    }

    private function CreateSceneScheduler()
    {
        // Scheduled Event
        $scheduler = $this->SceneScheduler();
        $scheduler->Register("Szenen Zeiten", self::PosSceneScheduler);
        $scheduler->SetIcon("Calendar");
        $scheduler->Show();
        $scheduler->SetGroup(0, IPSEventScheduler::DayWeekday);
        $scheduler->SetGroup(1, IPSEventScheduler::DayWeekend);

        for ($sceneNumber = 0; $sceneNumber<$this->Scenes()->Count(); $sceneNumber++) {
            $scene = $this->Scenes()->At($sceneNumber);

            $scheduler->SetAction($sceneNumber, $scene->Name(), $scene->Color(),
                "BL_SetSceneFromScheduler(\$_IPS['TARGET'], $sceneNumber);");
        }
    }

    private function CreateSceneSwitches()
    {
        $this->SceneSwitches()->RegisterTriggers();
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

    private function CreateTurnOffButton()
    {
        $this->TurnOffScript()->Register("Ausschalten", "<? BL_TurnOff(" . $this->InstanceID . ");?>", self::PosTurnOffButton);
    }

    private function CreateAlexaScript()
    {
        $this->AlexaScript()->Register("Alexa",
        ' <?
            if($_IPS[\'SENDER\'] == "AlexaSmartHome") {
                IPS_LogMessage("AlexaTest", "test1");
                BL_Alexa('.$this->InstanceID.',$_IPS[\'VARIABLE\'],$_IPS[\'VALUE\'],$_IPS[\'REQUEST\']);
            }
        ?>'
        );
    }

    public function Alexa($var, $value, $request)
    {
        $name = $this->GetNameForAlexaId($var);

        if ($name === "Fernsehlicht" ||
            $name === "Fernsehbeleuchtung" ||
            $name === "TV Licht" ||
            $name === "TV Beleuchtung") {
            if ($request === "TurnOnRequest") {
                $this->SetScene(2, true);
            } elseif ($request === "TurnOffRequest") {
                $this->SetScene(1, true);
            }
        } elseif ($name === "Standard Licht" ||
                  $name === "Normales Licht") {
            if ($request === "TurnOnRequest") {
                $this->SetScene(1, true);
            } elseif ($request === "TurnOffRequest") {
                $this->TurnOff();
            }
        } elseif ($name === "Helles Licht" ||
                  $name === "Helle Beleuchtung") {
            if ($request === "TurnOnRequest") {
                $this->SetScene(3, true);
            } elseif ($request === "TurnOffRequest") {
                $this->SetScene(1, true);
            }
        }
    }

    public function StartSave()
    {
        $this->Log("StartSave()");
        $this->SaveToSceneVar()->Show();
        $this->SaveSceneScript()->Hide();
    }

    private function SaveToScene($sceneNumber)
    {
        $this->Log("SaveToScene(sceneNumber = $sceneNumber)");

        $this->DimLights()->SaveToScene($sceneNumber);
        $this->SwitchLights()->SaveToScene($sceneNumber);
        $this->RGBLights()->SaveToScene($sceneNumber);

        $this->MotionSensor()->SaveToScene($sceneNumber);

        $this->CancelSave();
    }

    private function LoadFromScene($sceneNumber)
    {
        $this->Log("LoadFromScene(sceneNumber = $sceneNumber)");

        $triggerIdent = $this->IdendTriggerdTurnOnVar()->Value();
        $triggerValue = $this->IdendTriggerdTurnOnValueVar()->Value();

        $this->DimLights()->LoadFromScene($sceneNumber, $triggerIdent, $triggerValue);
        $this->SwitchLights()->LoadFromScene($sceneNumber, $triggerIdent, $triggerValue);
        $this->RGBLights()->LoadFromScene($sceneNumber, $triggerIdent, $triggerValue);

        // Motion Sensor is set in SetScene.
    }

    public function BackToCurrentScene()
    {
        $this->Log("BackToCurrentScene()");
        $this->SetScene($this->CurrentSceneVar()->Value(), false);
    }

    public function ToggleScene($sceneNumber)
    {
        $this->Log("ToggleScene(sceneNumber = $sceneNumber)");
        $currentScene = $this->CurrentSceneVar()->Value();

        if ($currentScene == $sceneNumber) {
            $this->SetScene(self::DefaultSceneNumber, true);
        } else {
            $this->SetScene($sceneNumber, true);
        }
    }

    public function SetSceneFromScheduler($sceneNumber)
    {
        $this->Log("SetSceneFromScheduler(sceneNumber = $sceneNumber)");
        $this->SetScene($sceneNumber);
    }

    public function SetScene($sceneNumber, $turnOn = false)
    {
        $this->Log("SetScene(sceneNumber = $sceneNumber, turnOn = $turnOn)");

        $this->CurrentSceneVar()->SetValue($sceneNumber);
        $this->CancelSave();

        $ms = $this->MotionSensor();
        $isOn = $ms->IsMainSwitchOn();

        $ms->LoadFromScene($sceneNumber);

        if ($isOn || $turnOn) {
            // In lock states Montion sensor sends switch on/off commands. This will handle the setting of scene light vars.
            if ($ms->LockState() == MotionSensor::StateAuto) {
                $this->LoadFromScene($sceneNumber);
            }

            $ms->TriggerExternMovement();
        }

        return;
    }

    public function CancelSave()
    {
        $this->SaveToSceneVar()->Hide();
        $this->SaveSceneScript()->Show();
    }

    // FIX: Remove storeVar. Save everything in a string.
    private function SetBackedValue($backing, $value, $storeVar)
    {
        $this->Log("SetBackedValue(value=$value, storeVar=$storeVar)");
        $this->CancelSave();

        $ms = $this->MotionSensor();
        $isOn = $ms->IsMainSwitchOn();
        $IsMSLocked = $ms->LockState() == MotionSensor::StateAlwaysOff;

        if ($IsMSLocked) {
            // if MS is locked we do not get a turn on event.
            $backing->SetValue($value);
            $this->SetScene($this->CurrentSceneVar()->Value());
        } else {
            if (!$isOn) {
                $this->IdendTriggerdTurnOnVar()->SetValue($backing->DisplayIdent());
                $storeVar->SetValue($value);
                $ms->TriggerExternMovement();
            }

            $backing->SetValue($value);
        }
    }

    public function RequestAction($ident, $value)
    {
        $this->Log("RequestAction - ident:$ident, value:$value");

        if ($this->RequestActionForLight($ident, $value)) {
            return;
        }

        if ($this->MotionSensor()->RequestAction($ident, $value)) {
            return;
        }

        switch ($ident) {
            case $this->SaveSceneScript()->Ident():
                $this->StartSave();
                break;

            case $this->CurrentSceneVar()->Ident():
                $this->SetScene($value, true);
                break;

            case $this->SaveToSceneVar()->Ident():
                $this->Log("RequestAction SaveToSceneVar - ident:$ident, value:$value");
                $this->SaveToScene($value);
                break;

            default:
                $this->Log("RequestAction default - ident:$ident, value:$value");
                $this->SetValueForIdent($ident, $value);
                $this->CancelSave();
        }
    }

    private function RequestActionForLight($ident, $value)
    {
        $light = $this->SwitchLights()->GetLightForDisplayIdent($ident);

        if ($light === false) {
            $light = $this->DimLights()->GetLightForDisplayIdent($ident);
        }

        if ($light === false) {
            $light = $this->RGBLights()->GetLightForDisplayIdent($ident);
        }

        if ($light !== false) {
            $this->Log("RequestAction Light - ident:$ident, value:$value");
            $identTrigger = $this->IdendTriggerdTurnOnValueVar();
            $this->SetBackedValue($light->DisplayVarBacking(), $value, $identTrigger);
            return true;
        }

        return false;
    }

    public function MSMainSwitchEvent()
    {
        $ms = $this->MotionSensor();
        $turnOn = $ms->IsMainSwitchOn();

        // FIXME: Why is $turnOn not set if it should be actually false?
        $this->Log("MSMainSwitchEvent - turnOn:$turnOn, msLockState:" . $ms->LockState());

        if ($turnOn) {
            $this->LoadFromScene($this->CurrentSceneVar()->Value());
        } else {
            $this->TurnOffAll();
        }

        $this->IdendTriggerdTurnOnVar()->SetValue("");
    }

    private function TurnOffAll()
    {
        $this->Log("TurnOffAll()");

        $this->DimLights()->TurnOff();
        $this->SwitchLights()->TurnOff();
        $this->RGBLights()->TurnOff();
    }

    public function TurnOff()
    {
        $this->Log("TurnOff()");

        $currentScene = $this->CurrentSceneVar()->Value();
        $this->SetScene(self::OffSceneNumber, true);
        $this->CurrentSceneVar()->SetValue($currentScene);

        $instanceId = $this->InstanceID();
        $script = "BL_BackToCurrentScene($instanceId);";

        $this->OffTimer()->StartTimer(self::OffTimerTime, $script);
    }
}
