<?php
/**
 * Created by PhpStorm.
 * User: zhangwenhao <zhangwenhao@ganji.com>
 * Date: 3/16/15
 * Time: 07:17
 */
require dirname ( __FILE__ ) . '/include/config.php';
class SaveToCalendar {

    protected $_url = '';
    protected $_ch = null;

    public function __construct() {
        $account = array(
            'server'=> 'p05',
            'id'    => Config::$ID,
            'user'  => Config::$USER,
            'pass'  => Config::$PASS,
        );
        $this->url = 'https://'.$account['server'].'-caldav.icloud.com/'.$account['id'].'/calendars/work/';
        $userpwd = $account['user'] .":". $account['pass'];

        $this->_ch = curl_init();
        curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->_ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($this->_ch, CURLOPT_USERPWD, $userpwd);
        curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    }

    public function run() {
        echo date("Y-m-d H:i:s:\n");
        $sql = "select * from house where start_time >= UNIX_TIMESTAMP() and calendar_saved = 0 order by start_time asc";
        $query = mysqli_connect(DbConfig::$HOST, DbConfig::$USERNAME, DbConfig::$PASSWORD, 'tool');
        $result = $query->query($sql);
        $totalNum = 0;
        $updateNum = 0;
        while($row = mysqli_fetch_assoc($result)) {
            $totalNum ++;
            $startTime = $row['start_time'] - 60;
            $endTime = $startTime + 3600;
            $ret = $this->putToCalendar($row['house_key'], $row['title'], $startTime, $endTime);
            if ('' == $ret) {
                $sql = "update house set calendar_saved = 1 where id = {$row['id']}";
                $query->query($sql);
                $updateNum++;
            }
        }
        echo "total: {$totalNum}, update: {$updateNum}\n";
    }

    public function putToCalendar($key, $summary, $startTime, $endTime, $description = '', $location = 'Office') {
        $uid = "house-{$key}";
        $url = $this->url . $uid . '.ics';
        $tstart = gmdate("Ymd\THis\Z", $startTime);
        $tend = gmdate("Ymd\THis\Z", $endTime);
        $tstamp = gmdate("Ymd\THis\Z");

        $body = <<<__EOD
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
DTSTAMP:$tstamp
DTSTART:$tstart
DTEND:$tend
UID:$uid
DESCRIPTION:$description
LOCATION:$location
SUMMARY:$summary
BEGIN:VALARM
X-WR-ALARMUID:{$uid}-alarm
ACTION:DISPLAY
TRIGGER:-PT5M
DESCRIPTION:Event reminder
END:VALARM
BEGIN:VALARM
X-WR-ALARMUID:{$uid}-alarm-2
ACTION:DISPLAY
TRIGGER:-PT0S
DESCRIPTION:Event reminder
END:VALARM
END:VEVENT
END:VCALENDAR
__EOD;

        $headers = array(
            'Content-Type: text/calendar; charset=utf-8',
            "User-Agent: DAVKit/4.0.1 (730); CalendarStore/4.0.1 (973); iCal/4.0.1 (1374); Mac OS X/10.6.2 (10C540)",
            'If-None-Match: *',
            'Expect: ',
            'Content-Length: '.strlen($body),
        );
        curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($this->_ch, CURLOPT_URL, $url);
        curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $body);
        $res = curl_exec($this->_ch);
        return $res;
    }

    public function __destruct() {
        curl_close($this->_ch);
    }
}

$ins = new SaveToCalendar();
$ins->run();
echo "Finished!\n";