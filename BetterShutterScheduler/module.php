<?
require_once(__DIR__ . "/../BetterBase.php");
require_once(__DIR__ . "/../IPS/IPS.php");

class BetterShutterScheduler extends BetterBase {
    protected function GetModuleName()
    {
        return "BSS";
    }

    // Properties
    private function ShutterGroupUpDownIdProp() {        
        return new IPSPropertyInteger($this, __FUNCTION__);
    }   

    private function IsDayIdProp() {
        return new IPSPropertyInteger($this, __FUNCTION__);
    }   

    // Variables
    private function TwilightCheck() {        
        return new IPSVarBoolean($this->InstanceID(), __FUNCTION__);
    }   

    private function CloseForDayDone() {        
        return new IPSVarBoolean($this->InstanceID(), __FUNCTION__);
    }   

    private function OpenForDayDone() {        
        return new IPSVarBoolean($this->InstanceID(), __FUNCTION__);
    }   

    // Triggers
    private function IsDayTrigger() {        
        return new IPSEventTrigger($this->InstanceID(), __FUNCTION__);
    }   

    // Scheduler
    private function Scheduler() {
        return new IPSEventScheduler($this->InstanceID(), __FUNCTION__);
    }

	public function Create() 
    {
		parent::Create();		

        $this->ShutterGroupUpDownIdProp()->Register();
        $this->IsDayIdProp()->Register();
	}
	
	public function ApplyChanges() 
    {
		parent::ApplyChanges();

        $this->CloseForDayDone()->Register();
        $this->OpenForDayDone()->Register();
		
        $twilightCheck = $this->TwilightCheck();
        $enabled->Register("Dämmerungsautomatik", "~Switch");
        $enabled->EnableAction();

        $this->IsDayTrigger()->Register("", $this->IsDayIdProp()->Value(), 'BSS_IsDayChanged($_IPS[\'TARGET\'], $_IPS[\'VALUE\']);', IPSEventTrigger::TypeChange);

        $scheduler = $this->Scheduler();
        $scheduler->Register("Wochenplan");
        $scheduler->SetIcon("Calendar");
        $scheduler->SetGroup(0, IPSEventScheduler::DayMonday +
            IPSEventScheduler::DayTuesday + 
            IPSEventScheduler::DayWednesday + 
            IPSEventScheduler::DayThursday);
        $scheduler->SetGroup(1, IPSEventScheduler::DayFriday);
        $scheduler->SetGroup(2, IPSEventScheduler::DaySaturday);
        $scheduler->SetGroup(3, IPSEventScheduler::DaySunday);
        $scheduler->SetAction(0, "Auf", 0x00FF00, "BSS_EarlierstOpen(\$_IPS['TARGET']);");        
        $scheduler->SetAction(1, "Zu", 0x0000FF, "BSS_LatestClose(\$_IPS['TARGET']);");
	}

    public function RequestAction($ident, $value) 
    {
        switch($ident) {
            case $this->TwilightCheck()->Ident():
                $this->SetValueForIdent($Ident, $Value);
                break;

            default:
                throw new Exception("Invalid Ident");
        }
    }

    public function IsDayChanged($isDay)
    {
        $this->Log("IsDayChanged(isDay:$isDay)");

        if($isDay)
            $this->OnDawn();
        else
            $this->OnSunset();
    }

    private function OnDawn()
    {        
        $closeForDayDone = $this->CloseForDayDone();

        if($this->TwilightCheck()->Value() && $closeForDayDone->Value())
        {
            $this->MoveUp();
        }

        $closeForDayDone->SetValue(false);
    }

    private function OnSunset()
    {
        $upDownId = $this->ReadPropertyInteger("upDownId");
        
    }

    public function EarlierstOpen()
    {
        $this->Log("EarlierstOpen");
    }

    public function LatestClose()
    {
        $this->Log("LatestClose");
    }

    private function MoveUp()
    {
        $this->Move(false);
    }

    private function MoveDown()
    {
        $this->Move(true);
    }

    private function Move($down)
    {
        $upDownId = $this->ShutterGroupUpDownIdProp()->Value();
        EIB_Switch(IPS_GetParent($upDownId), $down);
    }
}
?>
