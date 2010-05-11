
// Calendar language file
// Lanuage: Japanese
// Author: Mihai Bazon, <mihai_bazon@yahoo.com>
// Encoding: UTF-8
// Modified by: The Cacti Group
// Distributed under the same terms as the calendar itself.

// full day names
Calendar._DN 	= new Array("日曜日",
							"月曜日",
							"火曜日",
							"水曜日",
							"木曜日",
							"金曜日",
							"土曜日",
							"日曜日");

// short day names
Calendar._SDN 	= new Array("日",
							"月",
							"火",
							"水",
							"木",
							"金",
							"土",
							"日");

// full month names
Calendar._MN 	= new Array("1月",
							"2月",
							"3月",
							"4月",
							"5月",
							"6月",
							"7月",
							"8月",
							"9月",
							"10月",
							"11月",
							"12月");

// short month names
Calendar._SMN 	= new Array("1月",
							"2月",
							"3月",
							"4月",
							"5月",
							"6月",
							"7月",
							"8月",
							"9月",
							"10月",
							"11月",
							"12月");

// First day of the week. "0" means display Sunday first, "1" means display Monday first
Calendar._FD = 0;

// Tooltips, About page and date format
Calendar._TT 					= {};
Calendar._TT["INFO"] 			= "カレンダーについて";
Calendar._TT["PREV_YEAR"] 		= "前年";
Calendar._TT["PREV_MONTH"] 		= "前月";
Calendar._TT["GO_TODAY"] 		= "今日";
Calendar._TT["NEXT_MONTH"] 		= "翌月";
Calendar._TT["NEXT_YEAR"] 		= "翌年";
Calendar._TT["SEL_DATE"] 		= "日付選択";
Calendar._TT["DRAG_TO_MOVE"] 	= "ウィンドウの移動";
Calendar._TT["PART_TODAY"] 		= " (今日)";


// the following is to inform that "%s" is to be the first day of week
// %s will be replaced with the day name.
Calendar._TT["DAY_FIRST"] 		= "%sを最初に表示する";

// This may be locale-dependent.  It specifies the week-end days, as an array
// of comma-separated numbers.  The numbers are from 0 to 6: 0 means Sunday, 1
// means Monday, etc.
Calendar._TT["WEEKEND"] 		= "0,6";

Calendar._TT["CLOSE"] 			= "閉じる";
Calendar._TT["TODAY"] 			= "今日";
Calendar._TT["TIME_PART"] 		= "（シフトキーを押しながら）クリックまたはドラッグして、値を変更してください"

// date formats
Calendar._TT["DEF_DATE_FORMAT"]	= "y-mm-dd";
Calendar._TT["TT_DATE_FORMAT"]	= "%m月 %d日 (%a)";

Calendar._TT["WK"] 				= "週";
Calendar._TT["TIME"] 			= "時刻:";


Calendar._TT["ABOUT"] 			=
	"DHTML Date/Time Selector\n" +							// Do not translate this this
	"(c) dynarch.com 2002-2005 / Author: Mihai Bazon\n" + 	// Do not translate this this
	"最新バージョンの配布元: http://www.dynarch.com/projects/calendar/\n" +
	"GNU LGPLの下で配布されています。詳しくは、http://gnu.org/licenses/lgpl.htmlを参照して下さい。" +
	"\n\n" +
	"日付の選択の仕方:\n" +
	"- \xab, \xbbボタンで、年を選択します。\n" +
	"- " + String.fromCharCode(0x2039) + ", " + String.fromCharCode(0x203a) + "ボタンで、月を選択します。\n" +
	"- ボタンを押しっぱなしにすると、素早く選択できます。";

Calendar._TT["ABOUT_TIME"] =
	"\n\n" +
	"時刻の選択の仕方:\n" +
	"- 数字をクリックすると、その部分が増加します。\n" +
	"- シフトボタンを押しながら数字をクリックすると、その部分が減少します。\n" +
	"- クリックしてドラッグすると、素早く選択できます。";