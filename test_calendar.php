<?php
/**
 * Created by PhpStorm.
 * User: zhangwenhao <zhangwenhao@ganji.com>
 * Date: 3/22/15
 * Time: 09:51
 */
//在此設定想看的日曆ID，下列範例是英文版的臺灣節日行事曆
$calID = 'taiwan__en@holiday.calendar.google.com';
require_once 'vendor/autoload.php';
$gdataCal = new ZendGData\Calendar();
$query = $gdataCal->newEventQuery();
//原始範例中setUser給的參數是default 這樣的話是開啟範例中$client的主日曆
//但由網頁說明中我們可知用$cal可以開啟我們指定的任何一本日曆
$query->setUser($calID);
$query->setVisibility('public');
$query->setProjection('full');
$query->setOrderby('starttime');
$query->setStartMin('2008-02-01');
$query->setStartMax('2008-02-29');
$eventFeed = $gdataCal->getCalendarEventFeed($query);
foreach($eventFeed as $event){//在同一個query中能顯示的事件數量上限為25筆
    foreach ($event->when as $when) {
        echo "startTime:" . $when->startTime . "\n";//事件起始時間
        echo "endTime:" . $when->endTime . "\n";//事件結束時間
    }
    echo "recurrence:" . $event->recurrence->text . "\n";//循環事件才有內容
    foreach ($event->where as $where) {
        echo "where:" . $where->valueString . "\n";//地點
    }
    echo "content:" . $event->content->text . "\n";//詳情
    echo "updated:" . $event->updated->text . "\n";//最後修改時間
    echo "title:" . $event->title->text . "\n";//事項
    echo "id:" . $event->id->text . "\n\n";//事件id(平時看不到)
}