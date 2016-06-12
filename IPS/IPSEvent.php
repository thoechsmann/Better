<?
require_once(__DIR__ . "/IPSObject.php");

abstract class IPSEventNew extends IPSObjectNew
{
    public function SetScript($content)
    {
        IPS_SetEventScript($this->Id(), "$content;"); 
    }

    public function SetActive($value)
    {
        IPS_SetEventActive($this->Id(), $value);
    }

    public function Activate()
    {
        $this->SetActive(true);        
    }

    public function Deactivate()
    {
        $this->SetActive(false);        
    }

    public function SetLimit($count)
    {
        IPS_SetEventLimit($this->Id(), $count);
    }

    protected function IsCorrectObjectType($id)
    {
        if(!IPS_EventExists($id))
            throw new Exception("Ident with name ".$this->Ident()." is used for wrong object type");
            
        return IPS_GetVariable($id)["EventType"] == static::GetEventTypeId();
    }

    abstract protected function GetVarTypeId();
}

class IPSEventTrigger extends IPSEvent
{
    const TypeUpdate = 0;
    const TypeChange = 1;
    const TypeBigger = 2;
    const TypeSmaller = 3;
    const TypeValue = 4;

    public function Register($name, $targetId, $script, $type = IPSEventTrigger::TypeChange, $position = 0)
    {
        $this->_Register($name, $position);
        
        $this->Hide();
        $this->SetScript($script);
        $this->SetTrigger($type, $targetId); 
        $this->SetSubsequentExecution(true);
        $this->Activate();

        return $this->Id();
    }

    public function SetTrigger($type, $targetId)
    {
        IPS_SetEventTrigger($this->Id(), $type, $targetId);
    }

    public function SetSubsequentExecution($value)
    {
        IPS_SetEventTriggerSubsequentExecution($this->Id(), $value);
    }

    protected function GetVarTypeId() 
    {
        return 0;
    }
}

class IPSEventCyclicNew extends IPSEventNew
{
    const DayMonday = 1;
    const DayTuesday = 2;
    const DayWednesday = 4;
    const DayThursday = 8;
    const DayFriday = 16;
    const DaySaturday = 32;
    const DaySunday = 64;

    const DayWeekdays = 31;
    const DayWeekends = 96;
    const DayAll = 127;

    const DateTypeNone = 0;
    const DateTypeOnce = 1;
    const DateTypeDay = 2;
    const DateTypeWeek = 3;
    const DateTypeMonth = 4;
    const DateTypeYear = 5;

    const TimeTypeOnce = 0;
    const TimeTypeSecond = 1;
    const TimeTypeMinute = 2;
    const TimeTypeHour = 3;

    // Changed order or args
    public function Register($name, $script, $position = 0)
    {
        $this->_Register($name, $position);

        $this->SetScript($script);

        return $this->Id();
    }

    // Add some nicer functions.
    public function SetCyclic($dateType, $dateInterval, $days, $daysInterval, $timeType, $timeInterval)
    {
        IPS_SetEventCyclic($this->Id(), $dateType, $dateInterval, $days, $daysInterval, $timeType, $timeInterval);
    }

    public function SetTimeFrom($hour, $minute, $second)
    {
        IPS_SetEventCyclicTimeFrom($this->Id(), $hour, $minute, $second);
    }

    public function StartTimer($seconds, $script)
    {
        $link = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        if($link !== false)
        {
            IPS_DeleteEvent($link);
        }

        $this->Register("", $script);
        // $this->SetCyclic(self::DateTypeNone, 0, 0, 0, self::TimeTypeSecond, $seconds);

        $time = time() + $seconds;
        $this->SetTimeFrom(date("H", $time), date("i", $time), date("s", $time));

        $this->SetLimit(1);
        $this->Activate();
        $this->Hide();
    }

    protected function GetVarTypeId() 
    {
        return 1;
    }
}

class IPSEventSchedulerNew extends IPSEventNew
{
    const DayMonday = 1;
    const DayTuesday = 2;
    const DayWednesday = 4;
    const DayThursday = 8;
    const DayFriday = 16;
    const DaySaturday = 32;
    const DaySunday = 64;

    const DayWeekdays = 31;
    const DayWeekends = 96;
    const DayAll = 127;

    public function Register($name = "", $position = 0)
    {
        return parent::_Register($name, $position);
    }
    
    public function SetGroup($groupId, $days)
    {
        IPS_SetEventScheduleGroup($this->Id(), $groupId, $days);
    }

    public function SetGroupPoint($groupId, $pointId, $hour, $minute, $second, $actionId)
    {
        IPS_SetEventScheduleGroupPoint($this->Id(), $groupId, $pointId, $hour, $minute, $second, $actionId);
    }

    public function SetAction($actionId, $name, $color, $scriptContent)
    {
        IPS_SetEventScheduleAction($this->Id(), $actionId, $name, $color, $scriptContent);
    }

    protected function GetVarTypeId() 
    {
        return 2;
    }
}

?>