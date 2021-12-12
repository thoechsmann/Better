<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../IPS/IPS.php");

class BetterHeatingNew extends BetterBase
{

    const WindowStatusCount = 7;
    const BoostIncreaseMinutes = 30;
    const BoostMaxMinutes = 90;

    protected function GetModuleName()
    {
        return "BHN";
    }

    // Properties
    private function CurrentTempId()
    {
        return new IPSPropertyInteger($this, __FUNCTION__, "Aktuelle Temperatur");
    }

    private function CurrentTargetTempId()
    {
        return new IPSPropertyInteger($this, __FUNCTION__, "Aktuelle Soll Temperatur");
    }

    private function ControlValueId()
    {
        // Stellwert
        return new IPSPropertyInteger($this, __FUNCTION__, "Aktueller Stellwert");
    }

    private function TargetTempComfortId()
    {
        return new IPSPropertyInteger($this, __FUNCTION__, "Soll Temperatur Komfort");
    }

    private function ModeId()
    {
        return new IPSPropertyInteger($this, __FUNCTION__, "Heizmodus");
    }

    private function BoostId()
    {
        return new IPSPropertyInteger($this, __FUNCTION__, "Boost");
    }

    private function WindowStatusIds()
    {
        return new IPSPropertyArrayInteger($this, __FUNCTION__, BetterHeatingNew::WindowStatusCount, "Fenster Kontakt");
    }

    // Variables
    private function WindowOpenInfo()
    {
        return new IPSVarString($this->InstanceID(), __FUNCTION__);
    }

    private function Boost()
    {
        return new IPSVarBoolean($this->InstanceID(), __FUNCTION__);
    }

    private function BoostTime()
    {
        return new IPSVarInteger($this->InstanceID(), __FUNCTION__);
    }

    // Links
    private function CurrentTempLink()
    {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

    private function ModeLink()
    {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

    private function TargetTempComfortLink()
    {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

    private function CurrentTargetTempLink()
    {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

    private function ControlValueLink()
    {
        return new IPSLink($this->InstanceID(), __FUNCTION__);
    }

    // Events
    private function ModeScheduler()
    {
        return new IPSEventScheduler($this->InstanceID(), __FUNCTION__);
    }

    private function BoostTimer()
    {
        return new IPSEventCyclic($this->InstanceID(), __FUNCTION__);
    }

    // Trigger
    private function WindowStatusTrigger(int $i)
    {
        return new IPSEventTrigger($this->InstanceID(), __FUNCTION__ . $i);
    }

    private function ModeTrigger()
    {
        return new IPSEventTrigger($this->InstanceID(), __FUNCTION__);
    }

    public function Create()
    {
        parent::Create();

        $this->CurrentTempId()->Register();
        $this->CurrentTargetTempId()->Register();
        $this->ControlValueId()->Register();
        $this->TargetTempComfortId()->Register();
        $this->ModeId()->Register();
        $this->BoostId()->Register();

        $this->WindowStatusIds()->RegisterAll();
    }

    public function GetConfigurationForm()
    {
        $retVal = "{\"elements\":[";

        $propArray = array(
            $this->CurrentTargetTempId()->GetConfigurationFormEntry(),
            $this->ModeId()->GetConfigurationFormEntry(),
            $this->TargetTempComfortId()->GetConfigurationFormEntry(),
            $this->CurrentTempId()->GetConfigurationFormEntry(),
            $this->ControlValueId()->GetConfigurationFormEntry(),
            $this->BoostId()->GetConfigurationFormEntry());

        $propArray = array_merge($propArray, $this->WindowStatusIds()->GetAllConfigurationFormEntries());

        $retVal .= implode(",", $propArray);

        $retVal .= "]}";

        return $retVal;
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $this->WindowOpenInfo()->Register("Fenster ist geöffnet -> Heizung aus");

        $this->CurrentTempLink()->Register("Temperatur", $this->CurrentTempId()->Value(), 1);
        $this->ModeLink()->Register("Modus", $this->ModeId()->Value(), 2);
        $this->TargetTempComfortLink()->Register("Soll Temperatur (Komfort)",
            $this->TargetTempComfortId()->Value(), 3);
        $this->CurrentTargetTempLink()->Register("Soll Temperatur", $this->CurrentTargetTempId()->Value(), 3);
        $this->ControlValueLink()->Register("Stellwert", $this->ControlValueId()->Value(), 10);

        if ($this->HasBoostSetting()) {
            $profileName = "BHN_Boost";
            @IPS_DeleteVariableProfile($profileName);
            IPS_CreateVariableProfile($profileName, 0);
            IPS_SetVariableProfileAssociation($profileName, true, 'AN', '', 0xFF0000);
            IPS_SetVariableProfileAssociation($profileName, false, 'AUS', '', -1);

            $this->Boost()->Register("Boost", $profileName, 4);
            $this->Boost()->SetIcon("Flame");
            $this->EnableAction($this->Boost()->Ident());

            $this->BoostTime()->Register("BoostTime");
            $this->BoostTime()->Hide();
        }

        // Scheduled Event
        $scheduler = $this->ModeScheduler();
        $scheduler->Register("Wochenplan", 5);
        $scheduler->SetIcon("Calendar");
        $scheduler->SetGroup(0, IPSEventScheduler::DayMonday +
            IPSEventScheduler::DayTuesday +
            IPSEventScheduler::DayWednesday +
            IPSEventScheduler::DayThursday +
            IPSEventScheduler::DayFriday);
        $scheduler->SetGroup(1, IPSEventScheduler::DaySaturday +
            IPSEventScheduler::DaySunday);

        $scheduler->SetAction(0, "Komfort", 0xFF0000, "BHN_SetMode(\$_IPS['TARGET'], 1);");
        $scheduler->SetAction(1, "Standby", 0xFFFF00, "BHN_SetMode(\$_IPS['TARGET'], 2);");
        $scheduler->SetAction(2, "Nacht", 0x0000FF, "BHN_SetMode(\$_IPS['TARGET'], 3);");

        // Window triggers
        for ($i = 0; $i < BetterHeatingNew::WindowStatusCount; $i++) {
            $windowId = $this->WindowStatusIds()->ValueAt($i);
            if ($windowId != 0) {
                $this->WindowStatusTrigger($i)->Register("", $windowId, 'BHN_UpdateWindow($_IPS[\'TARGET\']);', IPSEventTrigger::TypeChange);
            }
        }

        $this->ModeTrigger()->Register("", $this->ModeId()->Value(), 'BHN_UpdateHeatingMode($_IPS[\'TARGET\']);', IPSEventTrigger::TypeUpdate);

        $this->UpdateWindow();
        $this->UpdateHeatingMode();
        $this->UpdateBoost();
    }

    public function UpdateWindow()
    {
        $openWindowCount = 0;

        for ($i = 0; $i < BetterHeatingNew::WindowStatusCount; $i++) {
            $id = $this->WindowStatusIds()->ValueAt($i);
            if ($id!=0 && GetValue($id) === true) {
                $openWindowCount++;
            }
        }

        $this->WindowOpenInfo()->SetHidden($openWindowCount == 0);
    }

    public function UpdateHeatingMode()
    {
        if($this->ModeId()->Value() == 0) return;

        $mode = GetValue($this->ModeId()->Value());
        $this->CurrentTargetTempLink()->SetHidden($mode == 1);
        $this->TargetTempComfortLink()->SetHidden($mode != 1);
    }

    public function SetMode(int $mode)
    {
        EIB_Scale(IPS_GetParent($this->ModeId()->Value()), $mode);
    }

    public function RequestAction($ident, $value)
    {
        switch ($ident) {
            case $this->Boost()->Ident():
                if ($value == false) {
                    $this->DeactivateBoost();
                } else {
                    $this->IncreaseBoost();
                }
                break;

            default:
                throw new Exception("Invalid Ident");
        }
    }

    private function HasBoostSetting()
    {
        return $this->BoostId()->Value() != 0;
    }

    private function SetBoost(bool $value)
    {
        EIB_Switch(IPS_GetParent($this->BoostId()->Value()), $value);
    }

    public function UpdateBoost()
    {
        if (!$this->HasBoostSetting()) {
            return;
        }

        $boostTime = $this->BoostTime()->Value();
        $boostTime--;
        $this->BoostTime()->SetValue($boostTime);

        if ($boostTime <= 0) {
            $this->DeactivateBoost();
        } else {
            $boostId = $this->Boost()->SetName("Boost ($boostTime Minuten)");
        }
    }

    private function DeactivateBoost()
    {
        $this->BoostTime()->SetValue(0);

        $this->Boost()->SetValue(false);
        $this->Boost()->SetName("Boost");

        $this->SetBoost(false);
        $this->BoostTimer()->StopTimer();
    }

    private function IncreaseBoost()
    {
        $boostTime = $this->BoostTime()->Value();
        $boostTime += BetterHeatingNew::BoostIncreaseMinutes;
        $boostTime = min($boostTime, BetterHeatingNew::BoostMaxMinutes);
        $boostTime = max($boostTime, BetterHeatingNew::BoostIncreaseMinutes);
        $this->BoostTime()->SetValue($boostTime);

        $this->Boost()->SetValue(true);
        $this->Boost()->SetName("Boost ($boostTime Minuten)");

        $this->SetBoost(true);
        $this->BoostTimer()->StartTimer(60 * $boostTime + 1, 'BHN_UpdateBoost($_IPS[\'TARGET\']);');
    }
}
