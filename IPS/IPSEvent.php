<?
require_once(__DIR__ . "/IPSObject.php");

abstract class IPSEvent extends IPSObject
{
    public function SetScript(string $content)
    {
        IPS_SetEventScript($this->Id(), "$content;"); 
    }

    public function SetActive(bool $value)
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

    public function SetLimit(int $count)
    {
        IPS_SetEventLimit($this->Id(), $count);
    }

    protected function IsCorrectObjectType(int $id)
    {
        if(!IPS_EventExists($id))
            throw new Exception("Ident with name ".$this->Ident()." is used for wrong object type");
            
        return IPS_GetEvent($id)["EventType"] == $this->GetEventTypeId();
    }

    protected function CreateObject()
    {
        return IPS_CreateEvent($this->GetEventTypeId());
    }
    
    protected function DeleteObject(int $id)
    {
        IPS_DeleteEvent($id);
    }

    abstract protected function GetEventTypeId();
}

class IPSEventTrigger extends IPSEvent
{
    const TypeUpdate = 0;
    const TypeChange = 1;
    const TypeBigger = 2;
    const TypeSmaller = 3;
    const TypeValue = 4;

    public function Register(string $name, int $targetId, string $script, int $type = IPSEventTrigger::TypeChange, int $position = 0)
    {
        $this->_Register($name, $position);
        
        $this->Hide();
        $this->SetScript($script);
        $this->SetTrigger($type, $targetId); 
        $this->SetSubsequentExecution(true);
        $this->Activate();

        return $this->Id();
    }

    public function SetTrigger(int $type, int $targetId)
    {
        IPS_SetEventTrigger($this->Id(), $type, $targetId);
    }

    public function SetSubsequentExecution(bool $value)
    {
        IPS_SetEventTriggerSubsequentExecution($this->Id(), $value);
    }

    protected function GetEventTypeId() 
    {
        return 0;
    }
}

class IPSEventCyclic extends IPSEvent
{
    // TODO: Duplicate. Move const days to IPSEvent.

    const DayMonday = 1;
    const DayTuesday = 2;
    const DayWednesday = 4;
    const DayThursday = 8;
    const DayFriday = 16;
    const DaySaturday = 32;
    const DaySunday = 64;

    const DayWeekday = 31;
    const DayWeekend = 96;
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
    public function Register(string $name, string $script, int $position = 0)
    {
        $this->_Register($name, $position);

        $this->SetScript($script);

        return $this->Id();
    }

    // Add some nicer functions.
    public function SetCyclic(int $dateType, int $dateInterval, int $days, int $daysInterval, int $timeType, int $timeInterval)
    {
        IPS_SetEventCyclic($this->Id(), $dateType, $dateInterval, $days, $daysInterval, $timeType, $timeInterval);
    }

    public function SetTimeFrom(int $hour, int $minute, int $second)
    {
        IPS_SetEventCyclicTimeFrom($this->Id(), $hour, $minute, $second);
    }

    public function StartTimer(int $seconds, string $script)
    {
        $this->StopTimer();

        $this->Register("", $script);
        // $this->SetCyclic(self::DateTypeNone, 0, 0, 0, self::TimeTypeSecond, $seconds);

        $time = time() + $seconds;
        $this->SetTimeFrom(date("H", $time), date("i", $time), date("s", $time));

        $this->SetLimit(1);
        $this->Activate();
        $this->Hide();
    }

    public function StopTimer()
    {
        $link = @IPS_GetObjectIDByIdent($this->id, $this->InstanceID);
        if($link !== false)
        {
            IPS_DeleteEvent($link);
        }
    }

    protected function GetEventTypeId() 
    {
        return 1;
    }
}

class IPSEventScheduler extends IPSEvent
{
    const DayMonday = 1;
    const DayTuesday = 2;
    const DayWednesday = 4;
    const DayThursday = 8;
    const DayFriday = 16;
    const DaySaturday = 32;
    const DaySunday = 64;

    const DayWeekday = 31;
    const DayWeekend = 96;
    const DayAll = 127;

    public function Register(string $name = "", int $position = 0)
    {
        return parent::_Register($name, $position);
    }
    
    public function SetGroup(int $groupId, int $days)
    {
        IPS_SetEventScheduleGroup($this->Id(), $groupId, $days);
    }

    public function SetGroupPoint(int $groupId, int $pointId, int $hour, int $minute, int $second, int $actionId)
    {
        IPS_SetEventScheduleGroupPoint($this->Id(), $groupId, $pointId, $hour, $minute, $second, $actionId);
    }

    public function SetAction(int $actionId, int $name, int $color, string $scriptContent)
    {
        IPS_SetEventScheduleAction($this->Id(), $actionId, $name, $color, $scriptContent);
    }

    protected function GetEventTypeId() 
    {
        return 2;
    }
}

?>