<?php
// error_reporting(E_ERROR | E_WARNING | E_PARSE);
if(!defined('PATH'))
{
    define('PATH',dirname(__FILE__));
}

class MainConfig 
{
    const DB_HOST = "127.0.0.1";
    const DB_USER = "root";
    const DB_PASS = "";
    const DB_NAME = "ypn";
	const DB_DEGUG = false;

    const DS = DIRECTORY_SEPARATOR;
    const ROOTPATH = PATH;
    const PREFIX = "ypn_";
    static $matchClasses = array(
        1 => "意大利甲级联赛",
        2 => "意大利杯八分之一决赛",
        3 => "欧洲冠军联赛小组赛",
        4 => "欧洲冠军联赛八分之一决赛",
        5 => "欧洲冠军联赛四分之一决赛",
        6 => "欧洲冠军联赛半决赛",
        7 => "欧洲冠军联赛决赛",
        8 => "意大利杯四分之一决赛",
        9 	=> "意大利杯半决赛",
        10 	=> "意大利杯决赛",
        11 	=> "意大利超级杯",
        12 	=> "欧洲联赛小组赛",
        13 	=> "欧洲联赛十六分之一决赛",
        14 	=> "欧洲联赛八分之一决赛",
        15 	=> "欧洲联赛四分之一决赛",
        16 	=> "欧洲联赛半决赛",
        17 	=> "欧洲联赛决赛",
        18 	=> "欧洲超级杯",
        19 	=> "贝卢斯科尼杯",
        20 	=> "世界俱乐部杯半决赛",
        21 	=> "世界俱乐部杯季军争夺战",
        22 	=> "世界俱乐部杯决赛",
        23 	=> "国家队友谊赛",
        24 	=> "友谊赛",
        25 	=> "世界杯小组赛",
        26 	=> "世界杯八分之一决赛",
        27 	=> "世界杯四分之一决赛",
        28 	=> "世界杯半决赛",
        29 	=> "世界杯决赛",
        30 	=> "世界杯三四名决赛",
        31 	=> "英格兰超级联赛",
        32 	=> "欧洲杯小组赛",
        33 	=> "欧洲杯四分之一决赛",
        34 	=> "欧洲杯半决赛",
        35 	=> "欧洲杯决赛",
        36 	=> "亚冠半决赛",
        37 	=> "亚冠决赛"
    );
    
    static $bakFifaDates = array("2014-03-05", "2013-11-19", "2013-11-18", "2013-11-17", "2013-11-16", "2013-11-15", "2013-10-15", "2013-10-14", "2013-10-13", "2013-10-12", "2013-10-11", "2013-09-10", "2013-09-09", "2013-09-08", "2013-09-07", "2013-09-06");

    static $trainings = array(
        1 => array('experience'=>'ShotAccurateExperience', 'title'=>'射门', 'skill'=>'ShotAccurate'),
        2 => array('experience'=>'PassExperience', 'title'=>'传球', 'skill'=>'pass'),
        3 => array('experience'=>'TackleExperience', 'title'=>'抢断', 'skill'=>'tackle'),
        4 => array('experience'=>'HeaderExperience', 'title'=>'头球', 'skill'=>'header'),
        5 => array('experience'=>'BallControlExperience', 'title'=>'控球', 'skill'=>'BallControl'),
        6 => array('experience'=>'BeatExperience', 'title'=>'过人', 'skill'=>'beat'),
        7 => array('experience'=>'SaveExperience', 'title'=>'守门', 'skill'=>'save'),
        8 => array('experience'=>'SinewMaxExperience', 'title'=>'体能', 'skill'=>'SinewMax'),
        9 => array('experience'=>'QiangdianExperience', 'title'=>'抢点', 'skill'=>'qiangdian'),
    );
    
    static $positions = array(
        1 => '前锋',
        2 => '后腰',
        3 => '中后卫',
        4 => '门将',
        5 => '左边锋',
        6 => '右边锋',
        7 => '中锋',
        8 => '前腰',
        9 => '左前卫',
        10 => '右前卫',
        13 => '左后卫',
        14 => '右后卫'
    );
    
    static $conditions = array(
        1 => '首发',
        2 => '板凳',
        3 => '场外',
        4 => '受伤',
        7 => '意外',
    );
    
    static $cornerPositions = array(
        1 => '前点',
        2 => '中点',
        3 => '后点',
        4 => '禁区外'
    );
    
    const STATIC_DIR = "http://test.ypn.com/";
	const BASE_URL = "http://test.ypn.com/";
	
	static $uclPlayoffDates = array(
		'8' => array(array('2-17','2-24'),array('2-18','2-25'),array('3-10','3-17'), array('3-9','3-16')), //八分之一
		'4' => array(array('4-6','4-14'),array('4-7','4-13')),
		'half' => array(array('4-27','5-5'),array('4-28','5-4')),
		'final' => '5-29'
	);
	
	static $elPlayoffDates = array(
		'16' => array(array('2-18','2-25'),array('2-19','2-26')),
		'8' => array(array('3-11','3-18')), //八分之一
		'4' => array(array('4-8','4-15')),
		'half' => array(array('4-29','5-6')),
		'final' => '5-19'
	);
}