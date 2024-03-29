<?

require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../IPS/IPS.php");

class BetterShutterSchedulerNew extends BetterBase
{
  const ShutterModuleIdCount = 20;

  protected function GetModuleName()
  {
    return "BSSN";
  }

  // Properties
  private function IsDayIdProp()
  {
    return new IPSPropertyInteger($this, __FUNCTION__);
  }

  // Variables
  private function TwilightCheck()
  {
    return new IPSVarBoolean($this->InstanceID(), __FUNCTION__);
  }

  private function OpenOnDawn()
  {
    return new IPSVarBoolean($this->InstanceID(), __FUNCTION__);
  }

  private function CloseForDayDone()
  {
    return new IPSVarBoolean($this->InstanceID(), __FUNCTION__);
  }

  // Triggers
  private function IsDayTrigger()
  {
    return new IPSEventTrigger($this->InstanceID(), __FUNCTION__);
  }

  // Scheduler
  private function Scheduler()
  {
    return new IPSEventScheduler($this->InstanceID(), __FUNCTION__);
  }

  public function GetConfigurationForm()
  {
    $retVal = "{\"elements\":[";

    $propArray = array(
      $this->IsDayIdProp()->GetConfigurationFormEntry()
    );

    $retVal .= implode(",", $propArray);

    $retVal .= "]}";

    return $retVal;
  }

  public function Create()
  {
    parent::Create();

    $this->IsDayIdProp()->Register();
  }

  public function ApplyChanges()
  {
    parent::ApplyChanges();

    $this->OpenOnDawn()->Register();
    $this->CloseForDayDone()->Register();

    $twilightCheck = $this->TwilightCheck();
    $twilightCheck->Register("Dämmerungsautomatik", "~Switch");
    $this->EnableAction($twilightCheck->Ident());

    $this->IsDayTrigger()->Register("", $this->IsDayIdProp()->Value(), 'BSSN_DayChanged($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', IPSEventTrigger::TypeChange);

    $scheduler = $this->Scheduler();
    $scheduler->Register("Wochenplan");
    $scheduler->SetIcon("Calendar");
    $scheduler->SetGroup(0, IPSEventScheduler::DayMonday +
      IPSEventScheduler::DayTuesday +
      IPSEventScheduler::DayWednesday +
      IPSEventScheduler::DayThursday +
      IPSEventScheduler::DayFriday);
    $scheduler->SetGroup(1, IPSEventScheduler::DaySaturday +
      IPSEventScheduler::DaySunday);

    $this->SetTwilightCheck($this->TwilightCheck()->Value());
  }

  public function RequestAction($ident, $value)
  {
    switch ($ident) {
      case $this->TwilightCheck()->Ident():
        $this->SetTwilightCheck($value);
        break;

      default:
        throw new Exception("Invalid Ident");
    }
  }

  private function SetTwilightCheck(bool $value)
  {
    $this->TwilightCheck()->SetValue($value);

    $scheduler = $this->Scheduler();

    if ($value) {
      $scheduler->SetAction(0, "frühstes Öffnen", 0x00FF00, "BSSN_EarliestOpen(\$_IPS['TARGET']);");
      $scheduler->SetAction(1, "spätestes Schliessen", 0x0000FF, "BSSN_LatestClose(\$_IPS['TARGET']);");
    } else {
      $scheduler->SetAction(0, "Öffnen", 0x00FF00, "BSSN_MoveUp(\$_IPS['TARGET']);");
      $scheduler->SetAction(1, "Schliessen", 0x0000FF, "BSSN_MoveDown(\$_IPS['TARGET']);");
    }
  }

  public function DayChanged(bool $isDay)
  {
    $this->Log("DayChanged(isDay:$isDay)");

    if ($isDay)
      $this->OnDawn();
    else
      $this->OnSunset();
  }

  private function OnDawn()
  {
    if (!$this->TwilightCheck()->Value())
      return;

    if ($this->OpenOnDawn()->Value()) {
      $this->MoveUp();
    }
  }

  private function OnSunset()
  {
    if (!$this->TwilightCheck()->Value())
      return;

    if ($this->CloseForDayDone()->Value() == false) {
      $this->MoveDown();
    }
  }

  public function EarliestOpen()
  {
    $this->Log("EarliestOpen");

    if ($this->IsDay()) {
      $this->MoveUp();
    } else {
      $this->OpenOnDawn()->SetValue(true);
    }
  }

  public function LatestClose()
  {
    $this->Log("LatestClose");

    $this->MoveDown();
  }

  private function IsDay()
  {
    return GetValue($this->IsDayIdProp()->Value());
  }

  public function MoveUp()
  {
    $this->Move(false);
    $this->CloseForDayDone()->SetValue(false);
    $this->OpenOnDawn()->SetValue(false);
  }

  public function MoveDown()
  {
    if ($this->CloseForDayDone()->Value() == false)
      $this->Move(true);

    $this->CloseForDayDone()->SetValue(true);
    $this->OpenOnDawn()->SetValue(false);
  }

  private function Move(bool $down)
  {
    $array = IPS_GetInstanceListByModuleID("{5B245BF6-5C02-4C68-9A0B-5CF08E6BEA87}"); // Get all BetterShutter instances   
    foreach ($array as $element) {
      if ($down)
        BSN_MoveDownCalledFromScheduler($element);
      else
        BSN_MoveUpCalledFromScheduler($element);
    }
  }
}
