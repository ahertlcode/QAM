<?php


class appmaker
{
    static protected $exception;
    static protected $db_config;
    static protected $fmts;
    static protected $base_dir;

    public static function makeapp($params){
        self::$exception = (isset($params[1]))? explode(",",str_replace("--exclude=","",$params[1])):"";
        self::$fmts = (isset($params[2]) && strpos($params[2], "--report")>-1)?
            explode(",",str_replace("--report=","",$params[2])):"";
        $dbconfig = explode(",",str_replace("--db=","",$params[0]));
        for ($i = 0; $i < sizeof($dbconfig); $i++){
            $tpair = explode(":",$dbconfig[$i]);
            self::$db_config[$tpair[0]] = $tpair[1];
        }
        $tbs = self::getTables();
        self::makeform($tbs);
        self::create_class($tbs);
        self::create_http_api($tbs);
        self::create_javascript($tbs);
        //self::writeconfigfile(self::$db_config);
    }

    private static function getTables(){
        $db = self::dataObj();
        $tables = $db->show_dbTables($db->dbname);
        return $tables;
    }

    private static function makemodel($tbs){
        foreach ($tbs as $table){
            if (self::exempted($table)===false){
                self::create_model($table);
            }
        }
    }

    private static function exempted($tb){
        $found = 0;
        foreach(self::$exception as $t){
            if ($t == $tb["Tables_in_".self::$db_config["db"]]){
                $found += 1;
            } else {
                $found += 0;
            }
        }
        if ($found > 0){
                return true;
            } else {
                return false;
        }
    }

    private static function dataObj(){
        $db = new DbHandlers();
        $db->servername = self::$db_config["host"];
        $db->dbname = self::$db_config["db"];
        $db->username = self::$db_config["user"];
        $db->password = self::$db_config["password"];
        return $db;
    }

    private static function writeconfigfile($conf){
        $fcon = '<?php '."\r\n//Database settings/configuration file.\r\n";
        $fcon .='    $db = new DbHandlers();'."\r\n\r\n";
        $fcon .= '    $db->servername = "'.$conf['host'].'";'."\r\n\r\n";
        $fcon .= '    $db->username = "'.$conf['user'].'";'."\r\n\r\n";
        $fcon .= '    $db->password = "'.$conf['password'].'";'."\r\n\r\n";
        $fcon .= '    $db->dbname = "'.$conf['db'].'";'."\r\n";
        utilities::writetofile($fcon, self::$base_dir."/server/", "dbconfig", "php");
    }

    private static function table_structure($tb, $dbo){
        $tbl = $tb["Tables_in_".$dbo->dbname];
        $table_properties = $dbo->tableDesc($tbl);
        return $table_properties;
    }

    private static function create_model($tb){
        $md = self::dataObj();
        $tabprop = self::table_structure($tb, $md);
        $file = "../".$md->dbname."/app/model/";
        self::parsemodel($tabprop, $file);
    }

    private static function parsemodel($table, $file){
        if(!file_exists($file)){
            mkdir($file);
        }
    }

    private static function makecontroller($tbs){

    }

    private static function makeform($tbs){
        $db = self::dataObj();
        foreach ($tbs as $table){
            if (self::exempted($table) === false){
                self::create_form($table);
            }
        }
        //self::createIndexfile(self::$base_dir."/app/views", $db->dbname);
        self::createLandingPage(self::$base_dir."/");
        self::generatemenu($tbs);
    }

    private static function generatemenu($tables){
        $menustr = '<nav class="navbar bg-dark navbar-dark">';
        $menustr .= "\r\n  ".'<ul class="navbar-nav">';
        $menustr .= '<h1>'.str_replace("db", "", ucwords(self::$db_config["db"])).'<sup>&reg;</sup></h1>';
        foreach ($tables as $tb){
            if (self::exempted($tb) === false){
                $menustr .= "\r\n    ".'<li class="nav-item">';
                $menustr .= "\r\n      ".'<a class="nav-link" href="http://localhost:8085/'.self::$db_config["db"].'/app/views/'.$tb["Tables_in_".self::$db_config["db"]].'/"><i class="fas fa-star"></i>  ';
                $menustr .= ucwords(str_replace("_"," ", $tb["Tables_in_".self::$db_config["db"]])).'</a>';
                $menustr .= "\r\n    ".'</li>';
            }
        }
        $menustr .= "\r\n  </ul>\r\n</nav>";
        utilities::writetofile($menustr, self::$base_dir."/app/views/", "menu", "html");
    }

    private static function create_form($table){
        $db = self::dataObj();
        $tabprop = self::table_structure($table, $db);
        $tbl = $table["Tables_in_".$db->dbname];
        self::$base_dir = "../".$db->dbname;
        $fdir = self::$base_dir."/app/views/".$tbl."/";
        self::parse_html_form($tabprop, $table, $fdir);
        if ($tbl == "users") {
            self::createSignUp($tabprop);
            self::createSignIn($tabprop);
        }
    }

    private static function createSignUp($prop){
        $sheader = self::fheaders();
        $signup = "\r\n  ".'<body>'."\r\n    ".'<div class="container-fluid">';
        $signup .= "\r\n      ".'<form method="post" enctype="multipart/form-data">';
        $signup .= "\r\n        ".'<div class="form-group">';
        $signup .= "\r\n          ".'<label for="fullname">Full Name</label>';
        $signup .= "\r\n          ".'<input class="form-control" name="fullname" type="text" ng-model="user.fullname" required>';
        $signup .= "\r\n          ".'<small>Please Enter your full name with surname first.</small>';
        $signup .= "\r\n        ".'</div>';
        $signup .= "\r\n        ".'<div class="form-group">';
        $signup .= "\r\n          ".'<label for="email">E-mail</label>';
        $signup .= "\r\n          ".'<input class="form-control" name="email" type="email" ng-model="user.email" required>';
        $signup .= "\r\n          ".'<small>Please Enter your email.</small>';
        $signup .= "\r\n        ".'</div>';
        $signup .= "\r\n        ".'<div class="form-group">';
        $signup .= "\r\n          ".'<label for="password">Choose Password</label>';
        $signup .= "\r\n          ".'<input class="form-control" name="password" type="password" ng-model="user.password" required>';
        $signup .= "\r\n          ".'<small>Choose a strong but easy to remember password.</small>';
        $signup .= "\r\n        ".'</div>';
        $signup .= "\r\n        ".'<div class="form-group">';
        $signup .= "\r\n          ".'<label for="cpassword">Confirm Password</label>';
        $signup .= "\r\n          ".'<input class="form-control" name="cpassword" type="password" required>';
        $signup .= "\r\n          ".'<small>Confirm your password.</small>';
        $signup .= "\r\n        ".'</div>';
        $signup .= "\r\n        ".'<button type="submit" class="btn btn-primary">&nbsp;Register&nbsp;</button>';
        $signup .= "\r\n      ".'</form>';
        $signup .= "\r\n    ".'</div>';
        $sscripts = self::fscripts();
        $sclose = "\r\n    ".'</body>'."\r\n  ".'</html>';
        $shtml = $sheader.$signup.$sscripts.$sclose;
        utilities::writetofile($shtml, self::$base_dir."/", "signup", "html");
    }

    private static function createSignIn($prop){
        $inheader = self::fheaders();
        $signin = "\r\n  ".'<body>'."\r\n    ".'<div class="container-fluid">';
        $signin .= "\r\n  ".'<form method="post" enctype="multipart/form-data">';
        $signin .= "\r\n    ".'<div class="form-group">';
        $signin .= "\r\n      ".'<label for="username">Username / E-mail</label>';
        $signin .= "\r\n      ".'<input class="form-control" name="username" type="text" ng-model="user.username" required>';
        $signin .= "\r\n      ".'<small>Please your username or e-mail.</small>';
        $signin .= "\r\n    ".'</div>';
        $signin .= "\r\n    ".'<div class="form-group">';
        $signin .= "\r\n      ".'<label for="password">Password</label>';
        $signin .= "\r\n      ".'<input class="form-control" name="password" type="password" ng-model="user.password" required>';
        $signin .= "\r\n      ".'<small>Enter your password.</small>';
        $signin .= "\r\n    ".'</div>';
        $signin .= "\r\n    ".'<button type="submit" class="btn btn-primary">&nbsp;Sign In&nbsp;</button>';
        $signin .= "\r\n  ".'</form>';
        $signin .= '</div>';
        $inscripts = self::fscripts();
        $inclose = "\r\n    ".'</body>'."\r\n  ".'</html>';
        $inbody = $inheader.$signin.$inscripts.$inclose;
        utilities::writetofile($inbody, self::$base_dir."/", "signin", "html");
    }

    private static function parse_html_form($tbpro, $file, $d_file){
        if(!file_exists($d_file)){
            mkdir($d_file, 0777, true);
            self::copy_assets();
        }
        $dbf = self::dataObj();
        $header = self::getheaders();
        $scripts = self::getscripts($file["Tables_in_".$dbf->dbname]);
        $bopen = self::getbodyopen($file["Tables_in_".$dbf->dbname]);
        $bclos = self::getbodyclose($file["Tables_in_".$dbf->dbname]);
        $body = self::dohtmlbody($tbpro, $file["Tables_in_".$dbf->dbname]);
        $body .= '<div class="display" id="dview">';
        $body .= self::do_html_table_report($file);
        $body .= "\r\n".'</div>'."\r\n";
        $body .= '    <div class="display" id="dsview">'."\r\n";
        $body .= '        <table>'."\r\n";
        foreach ($tbpro as $col){
            $body .= '            <tr><td align="right"><b>'.$col["Field"].':</b></td><td align="left">';
            $htab = "";
            $htab1 = $file["Tables_in_".$dbf->dbname];
            if ($htab1[strlen($htab1)-1] == "s")
                $htab = substr($htab1, 0, strlen($htab1)-1);
            else
                $htab = $htab1;
            $body .= "&nbsp;".'{{'.$htab.'.';
            $body .= $col["Field"].'}}</td></tr>'."\r\n";
        }
        $body .= '        </table>'."\r\n";
        $body .= '    </div>'."\r\n";
        $html = $header.$bopen.$body.$bclos.$scripts;
        $html .= '    </body>'."\r\n".'</html>';
        utilities::writetofile($html, $d_file, "index", "html");
        //self::create_form_new($tbpro, $d_file);
    }

    private static function getbodyclose($tbc){
        $clbo = '                    </div>'."\r\n";
        $clbo .= '                </div>'."\r\n";
        $clbo .= '            </div>'."\r\n";
        $clbo .='        </div>'."\r\n";
        $clbo .= '    </div>';
        return $clbo;
    }

    private static function getbodyopen($tbo){
        $opbody = '    <body onload="loadHtmlPage(\'../menu.html\', \'sidemenu\');toggle_display(\'dview\');">'."\r\n";
        $opbody .= '        <div class="container-fluid" style="padding:0; margin:0; top:0; left:0;">'." \r\n";
        $opbody .= '            <div ng-app="'.$tbo.'View" ng-controller="'.$tbo.'Ctrl" class="row">'."\r\n";
        $opbody .= '                <div id="sidemenu" class="col-xl-3 col-lg-3 col-md-3 d-lg-inline d-md-inline d-sm-none d-none"></div>'."\r\n";
        $opbody .= '                <div class="col-xl-9 col-lg-9 col-md-9 col-sm-12 col-xs-12">'."\r\n";
        $opbody .= '                    <div class="row">'."\r\n";
        $opbody .= '                        <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">'."\r\n";
        $opbody .= '                            <span class="alert alert-danger">{{ berror }}</span>'."\r\n";
        $opbody .= '                        </div>'."\r\n";
        $opbody .= '                        <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-xs-12">'."\r\n";
        $opbody .= '                            <ul class="menu">'."\r\n";
        $opbody .= '                                <li onclick="toggle_display(\'dview\',\'reload\');" class="rootmenu"><i class="fas fa-home" aria-hidden="true"></i><a href="#"> Home</a></li>'."\r\n";
        $opbody .= '                                <li class="rootmenu"><i class="fas fa-print" aria-hidden="true"></i><a href="#"> Print</a></li>'."\r\n";
        $opbody .= '                                <li class="rootmenu"><i class="fas fa-cloud-download-alt"></i><a href="#"> Export</a>'."\r\n";
        $opbody .= '                                    <ul id="menu_list">'."\r\n";
        $opbody .= '                                        <li><i class="fas fa-file-excel" aria-hidden="true"></i><a href="#">  Excel</a></li>'."\r\n";
        $opbody .= '                                        <li><i class="fas fa-file-alt" aria-hidden="true"></i><a href="#">  CSV</a></li>'."\r\n";
        $opbody .= '                                        <li><i class="fas fa-file-pdf" aria-hidden="true"></i><a href="#">  Pdf</a></li>'."\r\n";
        $opbody .= '                                        <li><i class="fas fa-file-alt" aria-hidden="true"></i><a href="#">  Text</a></li>'."\r\n";
        $opbody .= '                                        <li><i class="fas fa-file-word" aria-hidden="true"></i><a href="#">  Word</a></li>'."\r\n";
        $opbody .= '                                    </ul>'."\r\n";
        $opbody .= '                                </li>'."\r\n";
        $opbody .= '                                <li onclick="toggle_display(\'dnew\');" class="rootmenu"><i class="fas fa-copy" aria-hidden="true"></i><a href="#"> Add New</a></li>'."\r\n";
        $opbody .= '                            </ul>'."\r\n";
        $opbody .= '                        </div>'."\r\n";
        $opbody .= '                    </div>'."\r\n";
        $opbody .= '                <div class="row">'."\r\n";
        $opbody .= '                    <div>'."\r\n";
        return $opbody;
    }

    private static function create_form_new($tab, $formdir){
        $fnew = '<div class="container-fluid">';
        $fnew .= "\r\n  ".'<form method="post" enctype="multipart/form-data">';
        foreach($tab as $tb){
            //if($tb['Null'] == "NO" && $tb['Field'] !== "id" && $tb['Field'] !== "created_at" && $tb['Field'] !== "updated_at" && $tb['Field'] !== "deleted_at"){
            if($tb['Field'] !== "id" && $tb['Field'] !== "created_at" && $tb['Field'] !== "updated_at" && $tb['Field'] !== "deleted_at"){
                $type_size = self::getFieldType($tb['Type']);
                $type = self::getControlType($tb['Field'], $type_size);
                if ($type_size < 200 && strpos($tb['Field'], "ender")==false){
                    $fnew .= self::doControlField($tb['Field'], $formdir, $type);
                }
                if(strpos($tb['Field'], "ender")>-1 || strpos($tb['Field'], "sex")>-1){
                $fnew .= self::doGenderSelect($tb['Field'], $formdir);
            }
            if ($type_size > 200 || strpos($tb['Type'], "text") >-1) {
                $fnew .= self::doTextarea($tb['Field'], $formdir);
            } else {}
            }

        }
        $fnew .= "\r\n    ".'<button type="submit" class="btn btn-primary">&nbsp; Save &nbsp;</button>';
        $fnew .= "\r\n  ".'</form>'."\r\n".'</div>';
        utilities::writetofile($fnew, $formdir, "new", "html");
    }

    

    private static function getFieldType($fd){
        $type_size = 0;
        if(strpos($fd, "varchar")>-1){
            $type_size = (int)str_replace(")","",str_replace("varchar(","",$fd));
        }
        return $type_size;
    }

    private static function getControlType($field, $tsize){
        if( strpos($field, "email")>-1 ){
            $type = "email";
        } else if( strpos($field, "phone")>-1 || strpos($field, "mobilephone")>-1 ){
            $type = "tel";
        } else if ( strpos($field, "date")>-1 ){
            $type = "date";
        } else {
            $type = "text";
        }
        return $type;
    }

    private static function doGenderSelect($field, $file){
        $dselect = "\r\n     ".'<div class="form-group">';
        $dselect .= "\r\n       ".'<label for="'.$field.'">'.ucwords(str_replace("_"," ",$field)).'</label>';
        $stab = "";
        if ($file[strlen($file)-1] == "s")
            $stab = substr($file, 0, strlen($file)-1);
        else
            $stab = $file;
        $dselect .= "\r\n       ".'<select class="form-control" name="'.$field.'" ng-model="'.$stab.".".$field.'">';
        $dselect .= "\r\n         ".'<option value="Male">Male</option>';
        $dselect .= "\r\n         ".'<option value="Female">Female</option>';
        $dselect .= "\r\n       ".'</select>';
        $dselect .= "\r\n     ".'</div>';
        return $dselect;
    }

    private static function dohtmlbody($prop, $file){
        $html_body = '            <div class="display" id="dnew">'."\r\n";
        $btab = "";
        if($file[strlen($file)-1] == "s")
            $btab = substr($file, 0, strlen($file)-1);
        else
            $btab = $file;
        $html_body .= '                <form name="frm'.ucwords($btab).'"  method="post" enctype="multipart/form-data">'."\r\n";
        $html_body .= '                    <input #colname type="hidden" name="colname" ng-model="update.col_name" />'."\r\n";
        $html_body .= '                    <input #colvalue type="hidden" name="colvalue" ng-model="update.col_value" />'."\r\n";
        for ($l=0; $l<sizeof($prop); $l++) {
            $type_size = self::getFieldType($prop[$l]['Type']);
            $type = self::getControlType($prop[$l]['Field'], $type_size);
            if ($prop[$l]['Field'] !== "id" && $type_size < 200  && strpos($prop[$l]['Field'], "ender")==false){
                $html_body .= self::doControlField($prop[$l]['Field'], $file, $type);
            }
            if(strpos($prop[$l]['Field'], "ender")>-1 || strpos($prop[$l]['Field'], "sex")>-1){
                $html_body .= self::doGenderSelect($prop[$l]['Field'], $file);
            }
            if ($type_size > 200 || strpos($prop[$l]['Type'], "text") >-1) {
                $html_body .= self::doTextarea($prop[$l]['Field'], $file);
            } else {}
        }
        $hbtab = "";
        if ($file[strlen($file)-1] == "s")
            $hbtab = substr($file, 0, strlen($file)-1);
        else
            $hbtab = $file;
        $html_body .= "\r\n".'<button ng-click="'.$hbtab.'_save();" type="button" class="btn btn-primary">&nbsp;SAVE&nbsp;</button>'."\r\n";
        $html_body .= "</form>\r\n</div>";
        return $html_body;
    }

    private static function doControlField($field, $file, $type){
        $html_control = "\r\n     ".'<div class="form-group">';
        $html_control .= "\r\n      ".'<label for="'.$field.'">'.ucwords(str_replace("_"," ",$field)).'</label>';
        $dftab = "";
        if ($file[strlen($file)-1] == "s")
            $dftab = substr($file, 0, strlen($file)-1);
        else
            $dftab = $file;
        $html_control .= "\r\n      ".'<input type="'.$type.'" class="form-control" name="'.$field.'" ng-model="'.$dftab.".".$field.'" placeholder="Enter '.$field.'" />';
        $html_control .= "\r\n     </div>";
        return $html_control;
    }

    private static function doTextarea($field, $file){
        $dtexta = "\r\n     ".'<div class="form-group">';
        $dtexta .= "\r\n      ".'<label for="'.$field.'">'.ucwords(str_replace("_"," ",$field)).'</label>';
        $dtaba = "";
        if ($file[strlen($file)-1] == "s")
            $dtaba = substr($file, 0, strlen($file)-1);
        else
            $dtaba = $file;
        $dtexta .= "\r\n      ".'<textarea class="form-control" name="'.$field.'" ng-model="'.$dtaba.".".$field.'">Enter '.$field.'</textarea>';
        $dtexta .= "\r\n     </div>";
        return $dtexta;
    }

    private static function getscripts($tbi){
        $html_body = "\r\n    ".'<script language="javascript" type="text/javascript" src="../../../assets/js/jquery.min.js"></script>';
        $html_body .= "\r\n    ".'<script language="javascript" type="text/javascript" src="../../../assets/js/popper.min.js"></script>';
        $html_body .= "\r\n    ".'<script language="javascript" type="text/javascript" src="../../../assets/js/bootstrap.min.js"></script>';
        $html_body .= "\r\n    ".'<script language="javascript" type="text/javascript" src="../../../assets/js/angular.min.js"></script>';
        $html_body .= "\r\n    ".'<script language="javascript" type="text/javascript" src="../../../assets/js/jquery-ui.js"></script>';
        $html_body .= "\r\n    ".'<script language="javascript" type="text/javascript" src="../../../assets/js/jquery.datepick.min.js"></script>';
        $html_body .= "\r\n    ".'<script language="javascript" type="text/javascript" src="../../../assets/js/jquery.table2excel.min.js"></script>';
        $html_body .= "\r\n    ".'<script language="javascript" type="text/javascript" src="../../../assets/js/jquery.uploadfile.min.js"></script>';
        $html_body .= "\r\n    ".'<script language="javascript" type="text/javascript" src="../../../assets/js/utility.js"></script>';
        $html_body .= "\r\n    ".'<script language="javascript" type="text/javascript" src="../../../assets/js/autocomplete.js"></script>';
        $html_body .= "\r\n    ".'<script language="javascript" type="text/javascript" src="../../../assets/src/'.$tbi.'.js"></script>';
        return $html_body;
    }

    private static function getheaders(){
        $html_str = "<!DOCTYPE html> \r\n ".'<html lang="en">'." \r\n  ";
        $html_str .= "    <head> \r\n   ";
        $html_str .= '        <meta content="text/html" charset="utf-8" >'." \r\n   ";
        $html_str .= '        <meta name="viewport" content="width=device-width, initial-scale=1">'." \r\n   ";
        $html_str .= '        <link rel="stylesheet" href="../../../assets/css/bootstrap.min.css" >'." \r\n   ";
        $html_str .= '        <link rel="stylesheet" href="../../../assets/css/jquery-ui.css" >'." \r\n   ";
        $html_str .= '        <link rel="stylesheet" href="../../../assets/css/jquery.datepick.css" >'." \r\n   ";
        $html_str .= '        <link rel="stylesheet" href="../../../assets/css/custom/uploadfile.css" >'." \r\n   ";
        $html_str .= '        <link rel="stylesheet" href="../../../assets/css/fontawesome-all.min.css" >'." \r\n  ";
        $html_str .= '        <link rel="stylesheet" href="../../../assets/css/custom/slide-menu.css" >'."\r\n";
        $html_str .= '        <link rel="stylesheet" href="../../../assets/css/custom/table-header.css" >'."\r\n";
        $html_str .= '        <link rel="stylesheet" href="../../../assets/css/custom/view.css" >'."\r\n";
        $html_str .= "    </head>";
        return $html_str;
    }

    private static function createIndexfile($path, $db){
        $head = self::getheaders();
        $script = self::getscripts();
        $ibody = "\r\n".' <body onload="loadHtmlPage('."'".'menu.html'."', '".'sidemenu'."'".');" >';
        $ibody .= "\r\n".'  <div class="container-fluid" style="padding:0; margin:0; top:0; left:0;">';
        $ibody .= "\r\n".'      <div class="row">';
        $ibody .= "\r\n".'       <div id="sidemenu" class="col-xl-3 col-lg-3 col-md-3 d-lg-inline d-md-inline d-sm-none d-none">';
        $ibody .= "\r\n".'        </div><div class="col-xl-9 col-lg-9 col-md-9 col-sm-12 col-xs-12"><div id="displaywin"></div></div>'."\r\n";
        $ibody .= '        </div></div>';
        $ibody_close ="\r\n".'  </body>'."\r\n".' </html>';
        $bcontent = $head.$ibody.$script.$ibody_close;
        utilities::writetofile($bcontent, $path."/", "index", "html");
    }

    private static function fheaders(){
        $lheaders = "<!DOCTYPE html>\r\n".'  <html lang="en">'."\r\n";
        $lheaders .= "    <head>\r\n      <title>Educare&reg;::Portal</title>\r\n";
        $lheaders .= '        <meta content="text/html" charset="utf-8" >'."\r\n";
        $lheaders .= '        <meta name="viewport" content="width=device-width, initial-scale=1">'."\r\n";
        $lheaders .= '        <link rel="stylesheet" href="assets/css/bootstrap.min.css" >'."\r\n";
        $lheaders .= '        <link rel="stylesheet" href="assets/css/jquery-ui.css" >'."\r\n";
        $lheaders .= '        <link rel="stylesheet" href="assets/css/jquery.datepick.css" >'."\r\n";
        $lheaders .= '        <link rel="stylesheet" href="assets/css/uploadfile.css" >'."\r\n";
        $lheaders .= '        <link rel="stylesheet" href="assets/css/fontawesome-all.min.css" >'."\r\n";
        $lheaders .= '        <link rel="stylesheet" href="assets/css/custom/slide-menu.css" >'."\r\n";
        $lheaders .= '        <link rel="stylesheet" href="assets/css/custom/table-header.css" >'."\r\n";
        $lheaders .= "      </head>";
        return $lheaders;
    }

    private static function fscripts(){
        $lscripts = "\r\n".'      <script language="javascript" type="text/javascript" src="assets/js/jquery.min.js"></script>';
        $lscripts .= "\r\n".'      <script language="javascript" type="text/javascript" src="assets/js/popper.min.js"></script>';
        $lscripts .= "\r\n".'      <script language="javascript" type="text/javascript" src="assets/js/bootstrap.min.js"></script>';
        $lscripts .= "\r\n".'      <script language="javascript" type="text/javascript" src="assets/js/angular.min.js"></script>';
        $lscripts .= "\r\n".'      <script language="javascript" type="text/javascript" src="assets/js/jquery-ui.js"></script>';
        $lscripts .= "\r\n".'      <script language="javascript" type="text/javascript" src="assets/js/jquery.datepick.min.js"></script>';
        $lscripts .= "\r\n".'      <script language="javascript" type="text/javascript" src="assets/js/jquery.table2excel.min.js"></script>';
        $lscripts .= "\r\n".'      <script language="javascript" type="text/javascript" src="assets/js/jquery.uploadfile.min.js"></script>';
        $lscripts .= "\r\n".'      <script language="javascript" type="text/javascript" src="assets/js/utility.js"></script>';
        $lscripts .= "\r\n".'      <script language="javascript" type="text/javascript" src="assets/js/autocomplete.js"></script>';
        return $lscripts;
    }

    private static function createLandingPage($path){
        $lheaders = self::fheaders();
        $lbodyo = "\r\n    ".'<body>'."\r\n      ".'<div class="container-fluid">';
        $lbodyo .= "\r\n        ".'<div class="row header">';
        $lbodyo .= "\r\n          ".'<div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 col-xs-6"><h1>'.str_replace("db","",ucwords(self::$db_config["db"]))."<sup>&reg;</sup></h1>\r\n          ".'</div>';
        $lbodyo .= "\r\n          ".'<div class="col-xl-6 col-lg-6 col-md-6 d-xl-inline d-lg-inline d-md-inline d-sm-none d-none">&nbsp;</div>';
        $lbodyo .= "\r\n          ".'<div class="col-xl-3 col-lg-3 col-md-3 col-sm-6 col-xs-6 float-right text-right"><a href="signup.html">Sign Up</a> | <a href="signin.html">Sign In</a></div>';
        $lbodyo .= "\r\n        ".'</div>';
        $lbodyo .= "\r\n        ".'<div class="row content">&nbsp;</div>';
        $lbodyo .= "\r\n        ".'<div class="row footer">';
        $lbodyo .= "\r\n          ".'<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-xs-12">&copy;&nbsp;2018 AHER TECHNOLOGIES LIMITED</div>';
        $lbodyo .= "\r\n          ".'<div class="col-xl-6 col-lg-6 col-md-6 col-sm-6 col-xs-12"><a href="#">link 1</a> | <a href="#">link 2</a> | <a href="#">link 3</a> | <a href="#">link 4</a> | <a href="#">link 5</a> | <a href="#">link 6</a></div>';
        $lbodyo .= "\r\n        ".'</div>';
        $lbodyo .="\r\n      ".'</div>';
        $lscripts = self::fscripts();
        $lbodyc = "\r\n    ".'</body>'."\r\n  ".'</html>';
        $landpage = $lheaders.$lbodyo.$lscripts.$lbodyc;
        utilities::writetofile($landpage, $path, "index", "html");
    }

    private static function copy_assets(){
        if(!file_exists(self::$base_dir.'/assets/css'))
        {
            mkdir(self::$base_dir.'/assets/css', 0777, true);
            mkdir(self::$base_dir.'/assets/js', 0777, true);
            mkdir(self::$base_dir.'/assets/webfonts', 0777, true);
            mkdir(self::$base_dir.'/assets/ckeditor', 0777, true);
            mkdir(self::$base_dir.'/api/error', 0777, true);
            mkdir(self::$base_dir.'/server', 0777, true);
        }
        utilities::xcopy('css/', self::$base_dir.'/assets/css');
        utilities::xcopy('js/', self::$base_dir.'/assets/js');
        utilities::xcopy('webfonts/', self::$base_dir.'/assets/webfonts');
        utilities::xcopy('ckeditor/', self::$base_dir.'/assets/ckeditor');
        utilities::xcopy('server/', self::$base_dir.'/server');
        utilities::xcopy('server/error', self::$base_dir.'/api/error');
    }

    
    private static function makereport($tbs, $format=null){
        if (!is_null($format)) {
            foreach ($format as $rf) {
                if ($rf == "table") {
                    self::maketablereport($tbs);
                } else if ($rf == "list") {
                    //self::makelistreport($tbs);
                } else if ($rf == "card") {
                    //self::makecardreport($tbs);
                } else if ($rf == "timeline") {
                    //self::maketimelinereport($tbs);
                } else {}
            }
        }
    }

    private static function maketablereport($tables){
        foreach ($tables as $table) {
            if (self::exempted($table) === false){
                self::do_html_table_report($table);
            }
        }
    }

    private static function do_html_table_report($tb){
        $tbr = array();
        $kount = 0;
        $hdb = self::dataObj();
        $tbpro = self::table_structure($tb, $hdb);
        $tbl = $tb["Tables_in_".$hdb->dbname];
        foreach ($tbpro as $tbrow) {
            if ($tbrow['Null'] == "NO"){
                $tbr[$kount] = $tbrow;
                $kount += 1;
            }
        }
        $html_tb = "\r\n  ".'<table class="table table-hover table-dark table-bordered">';
        $html_tb .= '    <thead>'."\r\n".'        <tr><th>S/N</th>';
        for($i=0; $i<sizeof($tbr); $i++){
            if (strtoupper($tbr[$i]['Field'])!== "PASSWORD")
                $html_tb .= '<th>'.strtoupper(str_replace("_"," ",$tbr[$i]['Field'])).'</th>';
        }
        $html_tb .= '<th>&nbsp;</th>';
        $html_tb .= "\r\n    ".'</tr></thead><tbody>';
        $atab = "";
        if ($tbl[strlen($tbl)-1] == "s")
            $atab = substr($tbl, 0, strlen($tbl)-1);
        else
            $atab = $tbl;
        $html_tb .= '<tr ng-repeat="x in '.$tbl.'.'.$atab.'">';
        $html_tb .= '<td>{{$index + 1}}</td>';
        for ($t=0; $t<sizeof($tbr); $t++){
            if (strtoupper($tbr[$t]['Field'])!== "PASSWORD")
                $html_tb .= '<td>{{x.'.$tbr[$t]['Field'].'}}</td>';
        }
        $html_tb .= '<td align="center">&nbsp;';
        $html_tb .= '<i onclick="toggle_display(\'dnew\');" ng-click="do_'.$atab.'_update(\'id\', x.id);" class="fas fa-edit"></i>&nbsp;&nbsp;';
        $html_tb .= '<i onclick="toggle_display(\'dsview\');" ng-click="'.$atab.'_view_single(\'id\', x.id);" class="fas fa-eye"></i>&nbsp;&nbsp;';
        $html_tb .= '<i ng-click="'.$atab.'_delete(\'id\', x.id);" class="fas fa-trash"></i></td>&nbsp;';
        $html_tb .= "\r\n  ".'</tbody>'."\r\n".'</table>';
        //utilities::writetofile($html_tb, self::$base_dir."/app/views/".$tbl."/", "index", "html");
        return $html_tb;
    }

    private static function create_class($tables){
        foreach ($tables as $tb){
            if ( self::exempted($tb) === false ){
                self::do_php_class($tb);
            }
        }
    }

    private static function get_class_docstring(){
        $docstr = '/**'."\r\n";
        $docstr .= 'This php script implements '."\r\n\r\n";
        $docstr .= 'PHP Version 5+'."\r\n";
        $docstr .= '@Author: Abayomi Apetu'."\r\n";
        $docstr .= '*/'."\r\n\r\n";
        return $docstr;
    }

    private static function get_class_properties($table){
        $publ = array();
        $priv = array();
        $dbo = self::dataObj();
        $slen = strlen($table["Tables_in_".$dbo->dbname]);
        if ( substr($table["Tables_in_".$dbo->dbname], $slen-1) == 's' ){
            $nclass = substr($table["Tables_in_".$dbo->dbname], 0, $slen-1);
        } else {
            $nclass = $table["Tables_in_".$dbo->dbname];
        }

        $defc = 'require "DbHandlers.php";'."\r\n\r\n";
        //$defc .= 'require "dbconfig.php";'."\r\n\r\n";
        $defc .= 'class '.ucwords($nclass).'{'."\r\n\r\n";
        $tprop = self::table_structure($table, $dbo);
        $defc .= '    /** '."\r\n".'Object(class) properties.'."\r\n";
        foreach ($tprop as $tbrow) {
            if ($tbrow['Default'] !== "NULL"){
                array_push($publ, $tbrow['Field']);
            } else {
                array_push($priv, $tbrow['Field']);
            }
        }
        $defc .= self::do_public_prop($publ);
        $defc .= "\r\n".self::do_private_prop($priv);
        return $defc;
    }

    private static function do_public_prop($prop){
        $pub = "";
        if(sizeof($prop)>0){
            $pub = "     Object(class) public properties.\r\n"."*/ \r\n";
            foreach($prop as $ppp){
                $pub .= "    public $".$ppp.";\r\n";
            }
        }
        return $pub;
    }

    private static function do_private_prop($prop){
        $pri = "";
        if(sizeof($prop)>0){
            $pri = "    /** Object(class) private properties.*/ \r\n";
            foreach($prop as $ppp){
                $pri .= "    private $".$ppp.";\r\n";
            }
        }
        return $pri;
    }

    private static function get_class_constructor(){
        $ccon = "    public function _construct(){\r\n";
        $ccon .= "        /** Todo, add code for system initialization here!*/ \r\n";
        $ccon.= "    }";
        return $ccon;
    }

    private static function get_class_method_save($table){
        $dbc = self::dataObj();
        $tbn = $table["Tables_in_".$dbc->dbname];
        $tpr = self::table_structure($table, $dbc);
        $savestr ="    public function save(){\r\n";
        $savestr.='        $db = new DbHandlers();'."\r\n";
        $savestr.='        $sql = "INSERT INTO '.$tbn.'(";'."\r\n";
        $itr = 0;
        foreach($tpr as $field){
            $savestr.='        if (isset($this->'.$field['Field'].') && $this->'.$field['Field'].'!=="" ) {'."\r\n";
            if ($itr == 0 ){
            $savestr.='             $sql.= '."'".$field['Field']."';";
            } else {
                $savestr.='            $sql.= '."',".$field['Field']."';";
            }
            $itr += 1;
            $savestr .="    \r\n        }\r\n";
        }
        $savestr.='        $sql.= ") VALUES (";'."\r\n";
        $tr = 0;
        foreach($tpr as $fld){
            $savestr.='        if (isset($this->'.$fld['Field']. ') && $this->'.$fld['Field'].'!=="") {'."\r\n";
            if ($tr == 0 ){
                if ($fld['Type'] == "date" || $fld['Type'] == "datetime") {
                    if ($fld['Type'] == "date")
                        $savestr.='            $sql.="'."'".'".'.'substr($this->'.$fld['Field'].",0,10)".'."'."'".'"'.";";
                    else
                        $savestr.='            $sql.="'."'".'".'.'str_replace(".000Z", "", str_replace("T", " ",$this->'.$fld['Field']."))".'."'."'".'"'.";";
                } else {
                    $savestr.='            $sql.="'."'{".'$this->'.$fld['Field']."}'".'"'.";";
                }
            } else {
                if ($fld['Type'] == "date" || $fld['Type'] == "datetime") {
                    if ($fld['Type'] == "date")
                        $savestr.='            $sql.="'.",'".'".'.'substr($this->'.$fld['Field'].",0,10)".'."'."'".'"'.";";
                    else
                        $savestr.='            $sql.="'.",'".'".'.'str_replace(".000Z", "", str_replace("T", " ", $this->'.$fld['Field']."))".'."'."'".'"'.";";
                } else {
                    $savestr.='            $sql.="'.",'{".'$this->'.$fld['Field']."}'".'"'.";";
                }
            }
            $tr += 1;
            $savestr .="    \r\n        }\r\n";
        }
        $savestr.='        $sql.=")";'."\r\n";
        $savestr.='        $sql = str_replace("(,", "(", $sql);'."\r\n";
        $savestr.='        $savein = $db->executeQuery($sql);'."\r\n";
        $savestr.='        return $savein;'."\r\n";
        $savestr.="    }";
        return $savestr;
    }

    private static function get_class_method_update($tbu){
        $dbu = self::dataObj();
        $tbn = $tbu["Tables_in_".$dbu->dbname];
        $tpr = self::table_structure($tbu, $dbu);
        $updatestr ='    public function update($pvcol, $pval){'."\r\n";
        $updatestr.='        $db = new DbHandlers();'."\r\n";
        $updatestr.='        $sql = "UPDATE '.$tbn.' SET ";'."\r\n";
        $itr = 0;
        foreach($tpr as $field){
            $updatestr.='        if (isset($this->'.$field['Field'].') && $this->'.$field['Field'].'!=="" ) {'."\r\n";
            if ($itr == 0 ){
                if ($field['Type'] == "date" || $field['Type'] == "datetime"){
                    if ($field['Type'] == "date")
                        $updatestr.='             $sql.= " '.$field['Field'].' = '."'".'".'.'substr($this->'.$field['Field'].", 0, 10)".'."'."'".'"'.";";
                    else
                        $updatestr.='             $sql.= " '.$field['Field'].' = '."'".'".'.'str_replace(".000Z", "", str_replace("T", " ", $this->'.$field['Field']."))".'."'."'".'"'.";";
                } else {
                    $updatestr.='             $sql.= " '.$field['Field'].' = '."'{".'$this->'.$field['Field']."}'".'"'.";";
                }
            } else {
                if ($field['Type'] == "date" || $field['Type'] == "datetime"){
                    if ($field['Type'] == "date")
                        $updatestr.='            $sql.= ", '.$field['Field'].' = '."'".'".'.'substr($this->'.$field['Field'].", 0, 10)".'."'."'".'"'.";";
                    else
                        $updatestr.='            $sql.= ", '.$field['Field'].' = '."'".'".'.'str_replace(".000Z", "", str_replace("T", " ", $this->'.$field['Field']."))".'."'."'".'"'.";";
                } else {
                    $updatestr.='            $sql.= ", '.$field['Field'].' = '."'{".'$this->'.$field['Field']."}'".'"'.";";
                }
            }
            $itr += 1;
            $updatestr .="    \r\n        }\r\n";
        }
        $updatestr.='        $sql .=  " WHERE $pvcol = '."'".'$pval'."'".'";'."\r\n";
        $updatestr.='        $sql = str_replace("SET ,", "SET ", $sql);'."\r\n";
        $updatestr.='        $upd = $db->executeQuery($sql);'."\r\n";
        $updatestr.='        return $upd;'."\r\n";
        $updatestr.="    }";
        return $updatestr;
    }

    private static function get_class_method_delete($tbs){
        $dbd = self::dataObj();
        $tb = $tbs["Tables_in_".$dbd->dbname];
        $delstr = '    public  function delete($critcol, $critval){'."\r\n";
        $delstr .='        $db = new DbHandlers();'."\r\n";
        $delstr .='        $sql = "DELETE FROM '.$tb.' WHERE $critcol ='."'{".'$critval'."}'".'";'."\r\n";
        $delstr .='        $d_out = $db->executeQuery($sql);'."\r\n";
        $delstr .='        return $d_out;'."\r\n";
        $delstr .='    }';
        return $delstr;
    }

    private static function get_Class_method_view($tbv){
        $dbv = self::dataObj();
        $tb = $tbv["Tables_in_".$dbv->dbname];
        //$tpv = self::table_structure($tbv, $dbv);
        $viewstr = '    public function view($critcol=null, $critval=null) {'."\r\n";
        $viewstr .= '        $db = new DbHandlers();'."\r\n";
        $viewstr .= '        if(is_null($critcol)){'."\r\n";
        $viewstr .= '            $sql = "SELECT * from '.$tb.' order by id DESC";'."\r\n        }";
        $viewstr .= ' else {'."\r\n";
        $viewstr .= '        $sql = "SELECT * from '.$tb.' WHERE $critcol ='."'{".'$critval'."}'".'";'."\r\n";
        $viewstr .= '        }'."\r\n";
        $viewstr .= '        $datasource = $db->getRowAssoc($sql);'."\r\n";
        $viewstr .= '        return $datasource;'."\r\n";
        $viewstr .= '    }';
        return $viewstr;
    }

    private static function do_php_class($table){
        $dbf = self::dataObj();
        $tbl = $table["Tables_in_".$dbf->dbname];
        $phpcode = "<?php\r\n";
        $phpcode .= self::get_class_docstring();
        $phpcode .= "\r\n".self::get_class_properties($table);
        $phpcode .= "\r\n\r\n".self::get_class_constructor();
        $phpcode .= "\r\n\r\n".self::get_class_method_save($table);
        $phpcode .= "\r\n\r\n".self::get_class_method_update($table);
        $phpcode .= "\r\n\r\n".self::get_Class_method_view($table);
        $phpcode .= "\r\n\r\n".self::get_class_method_delete($table);
        $phpcode .= "\r\n}";
        utilities::writetofile($phpcode, self::$base_dir."/server/", $tbl, "php");
    }

    private static function create_http_api($tables){
        foreach ($tables as $table) {
            if (self::exempted($table) === false){
                self::do_http_api($table);
            }
        }
    }

    private static function get_api_header($tbl){
        $ahstr = 'header("Access-Control-Allow-Origin: *");'."\r\n";
        $ahstr .= 'header("Content-Type: text/json");'."\r\n";
        $ahstr .= 'ini_set("memory_limit", "1024M");'."\r\n\r\n";
        $ahstr .= 'require "../server/renderer.php";'."\r\n";
        $ahstr .= 'require "../server/'.$tbl.'.php";'."\r\n\r\n";
        return $ahstr;
    }

    private static function get_api_data(){
        $datastr = '//retrieve API params/data from calling service.'."\r\n";
        $datastr .='if(isset($_POST) && !empty($_POST))'."\r\n";
        $datastr .='    $data = (object)$_POST;'."\r\n";
        $datastr .='else'."\r\n";
        $datastr .='    $data = json_decode(file_get_contents("php://input"));'."\r\n\r\n";
        $datastr .='if(isset($data)){'."\r\n";
        $datastr .="    //strip the trailing 's' character from the table ";
        $datastr .="name to created the model reference name.\r\n";
        $datastr .='    if($data->table[strlen($data->table)-1] == "s"){'."\r\n";
        $datastr .='        $obj = substr($data->table, 0, strlen($data->table)-1);'."\r\n";
        $datastr .="    } else {\r\n";
        $datastr .='        $obj = $data->table;'."\r\n}\r\n\r\n";
        $datastr .="//capitalize the first character of the class name.\r\n";
        $datastr .='$objc = ucwords($obj);'."\r\n\r\n";
        $datastr .="//create an instance of the model.\r\n";
        $datastr .='$$obj = new $objc();'."\r\n\r\n";
        $datastr .="//extract key from data using key value pair.\r\n";
        $datastr .='if (isset($data->data) && !empty($data->data)) {'."\r\n";
        $datastr .='    $dobc = (object)$data->data;'."\r\n";
        $datastr .='    foreach($dobc as $ky => $v) {'."\r\n";
        $datastr .='        $$obj->$ky = $dobc->$ky;'."\r\n    }\r\n}\r\n";
        return $datastr;
    }

    private static function get_api_save(){
        $savestr = "//save the data to to model.\r\n";
        $savestr .='if($data->method == "save") {'."\r\n";
        $savestr .='    $sout = $$obj->save();'."\r\n";
        $savestr .='    if ($sout == 1)'."\r\n";
        $savestr .='        $sout = array("status"=>"success", "msg"=>';
        $savestr .='"{$objc} saved successfully.");'."\r\n";
        $savestr .='    else'."\r\n";
        $savestr .='        $sout = array("status"=>"fail", "msg"=>';
        $savestr .='"{$objc} could not be saved.");'."\r\n";
        $savestr .='}'."\r\n";
        return $savestr;
    }

    private static function get_api_update(){
        $updatestr = "//update the model with supplied dataset.\r\n";

        $updatestr .='if($data->method == "update") {'."\r\n";
        $updatestr .='    if (isset($data->data->col_name) && ';
        $updatestr .='isset($data->data->col_value)){'."\r\n";
        $updatestr .='        $sout = $$obj->update($data->data->col_name, $data->data->col_value);'."\r\n";
        $updatestr .='        if($sout == 1)'."\r\n";
        $updatestr .='            $sout = array("status"=>"success", "msg"=>';
        $updatestr .='"{$objc} updated successfully.");'."\r\n";
        $updatestr .='        else'."\r\n";
        $updatestr .='            $sout = array("status"=>"failed", "msg"=>';
        $updatestr .='"There is an error, {$objc} could not be updated.");'."\r\n";
        $updatestr .='    } else {'."\r\n";
        $updatestr .='        $sout = array("status"=>"warning", "msg"=>';
        $updatestr .='"To update you must specific a criteria.");'."\r\n";
        $updatestr .='    }'."\r\n}";
        return $updatestr;
    }

    private static function get_api_view(){
        $viewstr = "//retrieve records from the model.\r\n";
        $viewstr .='if($data->method == "view") {'."\r\n";
        $viewstr .='    if (isset($data->data->col_name) && ';
        $viewstr .='isset($data->data->col_value))'."\r\n";
        $viewstr .='        $sout = $$obj->view($data->data->col_name, ';
        $viewstr .='$data->data->col_value);'."\r\n";
        $viewstr .='    else'."\r\n";
        $viewstr .='        $sout = $$obj->view();'."\r\n}";
        return $viewstr;
    }

    private static function get_api_delete(){
        $delstr = "//delete record from model.\r\n";

        $delstr .='if($data->method == "delete") {'."\r\n";
        $delstr .='    if (isset($data->data->col_name) && ';
        $delstr .='isset($data->data->col_value)){'."\r\n";
        $delstr .='        $sout = $$obj->delete($data->data->col_name';
        $delstr .=', $data->data->col_value);'."\r\n";
        $delstr .='        if($sout == 1)'."\r\n";
        $delstr .='            $sout = array("status"=>"success", "msg"=>';
        $delstr .='"{$objc} deleted successfully.");'."\r\n";
        $delstr .='        else'."\r\n";
        $delstr .='            $sout = array("status"=>"failed", "msg"=>';
        $delstr .='"There is an error, {$objc} could not be deleted.");'."\r\n";
        $delstr .='    } else {'."\r\n";
        $delstr .='        $sout = array("status"=>"warning", "msg"=>';
        $delstr .='"To delete you must specific a criteria.");'."\r\n    }\r\n}\r\n";
        return $delstr;
    }

    private static function get_api_close(){
        $clstr = "//send out put to the stand output.\r\n";
        $clstr .= '$rnd = new renderer();'."\r\n";
        //$clstr .= 'if (sizeof($sout)<2)'."\r\n";
        //$clstr .= '    $sout[0] = $sout;'."\r\n";
        $clstr .= 'echo $rnd->render("json", $sout, "{$obj}, <list></list>");'."\r\n";
        $clstr .= '}'."\r\n";
        return $clstr;
    }

    private static function do_http_api($table){
        $dbh = self::dataObj();
        $tbh = $table["Tables_in_".$dbh->dbname];
        $apistr = "<?php\r\n";
        $apistr .= self::get_class_docstring();
        $apistr .= "\r\n".self::get_api_header($tbh);
        $apistr .= "\r\n".self::get_api_data();
        $apistr .= "\r\n".self::get_api_save();
        $apistr .= "\r\n".self::get_api_update();
        $apistr .= "\r\n".self::get_api_view();
        $apistr .= "\r\n".self::get_api_delete();
        $apistr .= "\r\n".self::get_api_close();
        utilities::writetofile($apistr, self::$base_dir."/api/", $tbh."_http_api", "php");
    }

    private static function create_javascript($tbls){
        foreach ($tbls as $table) {
            if (self::exempted($table) === false){
                self::do_js_file($table);
            }
        }
    }

    private static function get_table_columns($tbl){
        $dbc = self::dataObj();
        $tab = self::table_structure($tbl, $dbc);
        $tcolumns = array();
        foreach ($tab as $row){
            array_push($tcolumns, $row['Field']);
        }
        return $tcolumns;
    }

    private static function get_tab_string($tb){
        $cols = self::get_table_columns($tb);
        $dstr = "";
        $iter = 0;
        foreach ($cols as $dcel){
            if($iter < sizeof($cols)-1){
            $dstr .= $dcel.":'', ";
            $iter++;
            } else { $dstr .= $dcel.":''"; }
        }
        return $dstr;
    }

    private static function get_js_save_method($tbs){
        $tb = "";
        if ($tbs[strlen($tbs)-1] == "s")
            $tb = substr($tbs, 0, strlen($tbs)-1);
        else
            $tb = $tbs;
        $jsave = '    $scope.'.$tb.'_save = function() {'."\r\n";
        $jsave .='        if (this.update == undefined) {'."\r\n";
        $jsave .='            var pb = {"method":"save", "table":"'.$tbs.'", "data":this.'.$tb.'};'."\r\n";
        $jsave .='            save_'.$tb.'($scope, $http, pb);'."\r\n";
        //$jsave .='            page_reload();'."\r\n";
        $jsave .='        } else {'."\r\n";
        $jsave .='            var data = Object.assign(this.'.$tb.', this.update);'."\r\n";
        $jsave .='            var pu = {"method":"update", "table":"'.$tbs.'", "data":data};'."\r\n";
        $jsave .='            update_'.$tb.'($scope, $http, pu);'."\r\n";
        //$jsave .='            page_reload();'."\r\n";
        $jsave .='        }'."\r\n".'    };'."\r\n\r\n";
        return $jsave;
    }

    private static function get_js_view_method($tbl){
        $tb = "";
        if ($tbl[strlen($tbl)-1] == "s")
            $tb = substr($tbl, 0, strlen($tbl)-1);
        else
            $tb = $tbl;
        $jsview = '    $scope.'.$tb.'_view_single = function(coln, colv){'."\r\n";
        $jsview .='        show_selected($scope, $http, coln, colv);'."\r\n";
        $jsview .='    };'."\r\n\r\n";
        return $jsview;
    }

    private static function get_js_update_method($tbl){
        $tb = "";
        if ($tbl[strlen($tbl)-1] == "s")
            $tb = substr($tbl, 0, strlen($tbl)-1);
        else
            $tb = $tbl;
        $upstr = '    $scope.do_'.$tb.'_update = function(colname, colvalue){'."\r\n";
        $upstr .='        $scope.update = {"col_name":colname, "col_value":colvalue};'."\r\n";
        $upstr .='        show_selected($scope, $http, colname, colvalue);'."\r\n";
        $upstr .='    };'."\r\n\r\n";
        return $upstr;
    }

    private static function get_js_delete_method($tbl){
        $tb = "";
        if ($tbl[strlen($tbl)-1] == "s")
            $tb = substr($tbl, 0, strlen($tbl)-1);
        else
            $tb = $tbl;
        $delstr = '    $scope.'.$tb.'_delete = function(coln, colv){'."\r\n";
        $delstr .='        delete_'.$tb.'($scope, $http, coln, colv);'."\r\n";
        $delstr .='    };'."\r\n\r\n";
        return $delstr;
    }

    private static function get_js_main_view($tbl){
        $tb = "";
        if ($tbl[strlen($tbl)-1] == "s")
            $tb = substr($tbl, 0, strlen($tbl)-1);
        else
            $tb = $tbl;
        $mainstr ='    $http({'."\r\n";
        $mainstr .='        url: base_api_url+"'.$tbl.'_http_api.php",'."\r\n";
        $mainstr .='        method: "POST",'."\r\n";
        $mainstr .='        data:{"method":"view", "table":"'.$tbl.'"}'."\r\n";
        $mainstr .='    }).then((result) =>{'."\r\n";
        $mainstr .='        $scope.'.$tbl.' = result.data;'."\r\n";
        $mainstr .='    }, function(error){'."\r\n";
        $mainstr .='        $scope.berror = error.statusText;'."\r\n";
        $mainstr .='    });'."\r\n\r\n";
        return $mainstr;
    }

    private static function get_js_save($tbl){
        $tb = "";
        if ($tbl[strlen($tbl)-1] == "s")
            $tb = substr($tbl, 0, strlen($tbl)-1);
        else
            $tb = $tbl;
        $gstr = 'function save_'.$tb.'($scope, $http, pb){'."\r\n";
        $gstr .='    $http({'."\r\n";
        $gstr .='        url: base_api_url+"'.$tbl.'_http_api.php",'."\r\n";
        $gstr .='        method: "POST",'."\r\n";
        $gstr .='        data:pb'."\r\n";
        $gstr .='    }).then((result) =>{'."\r\n";
        $gstr .='        $scope.berror = result.data.msg;'."\r\n";
        $gstr .='    }, function(error){'."\r\n";
        $gstr .='        $scope.berror = error.statusText;'."\r\n";
        $gstr .='    });'."\r\n".'}'."\r\n\r\n";
        return $gstr;
    }

    private static function get_js_view($tbl){
        $tb = "";
        if ($tbl[strlen($tbl)-1] == "s")
            $tb = substr($tbl, 0, strlen($tbl)-1);
        else
            $tb = $tbl;
        $vstr = 'function show_selected($scope, $http, column, value){'."\r\n";
        $vstr .='    $http({'."\r\n";
        $vstr .='        url: base_api_url+"'.$tbl.'_http_api.php",'."\r\n";
        $vstr .='        method: "POST",'."\r\n";
        $vstr .='        data:{"method":"view", "table":"'.$tbl.'", "data":';
        $vstr .='{"col_name":column, "col_value":value}}'."\r\n";
        $vstr .='    }).then((result) =>{'."\r\n";
        $vstr .='        $scope.'.$tb.' = result.data.'.$tb.';'."\r\n";
        $vstr .='    }, function(error){'."\r\n";
        $vstr .='        $scope.berror = error.statusText;'."\r\n";
        $vstr .='    });'."\r\n".'}'."\r\n\r\n";
        return $vstr;
    }

    private static function do_js_file($tb){
        $jdb = self::dataObj();
        $jtb = $tb["Tables_in_".$jdb->dbname];
        $tbj = "";
        if ($jtb[strlen($jtb)-1] == "s")
            $tbj = substr($jtb, 0, strlen($jtb)-1);
        else
            $tbj = $jtb;
        $jstr ="//javascript file for ".$jtb." using angularjs for data-binding.\r\n";
        $jstr .='var base_api_url = "http://localhost:8085/'.$jdb->dbname.'/api/";'."\r\n";
        $jstr .='var user = local_store({}, "'.str_replace("db", "",$jdb->dbname).'-user", "get");'."\r\n";
        $jstr .="var app = angular.module('".$jtb."View', []);\r\n\r\n";
        $jstr .='app.controller ('."'".$jtb."Ctrl'".', function($scope, $http) {'."\r\n\r\n";
        $jstr .='    this.'.$tbj.' = {user:user, ';
        $jstr .= self::get_tab_string($tb);
        $jstr .='};'."\r\n";
        $jstr .="    this.update = {col_name:'', col_value:''};\r\n\r\n";
        $jstr .= self::get_js_save_method($jtb); //create and update method
        $jstr .= self::get_js_view_method($jtb); //retrieve method
        $jstr .= self::get_js_update_method($jtb); //update method trigger
        $jstr .= self::get_js_delete_method($jtb); //delete method
        $jstr .= self::get_js_main_view($jtb); //main model view
        $jstr .="});\r\n\r\n";
        $jstr .= self::get_js_save($jtb);
        $jstr .= self::get_js_view($jtb);
        $jstr .= self::get_js_update($jtb);
        $jstr .= self::get_js_delete($jtb);
        utilities::writetofile($jstr, self::$base_dir."/assets/src/", $jtb, "js");
    }

    private static function get_js_update($tbl){
        $tb = "";
        if ($tbl[strlen($tbl)-1] == "s")
            $tb = substr($tbl, 0, strlen($tbl)-1);
        else
            $tb = $tbl;
        $jstr ='function update_'.$tb.'($scope, $http, pb){'."\r\n";
        $jstr .='    $http({'."\r\n";
        $jstr .='        url: base_api_url+"'.$tbl.'_http_api.php",'."\r\n";
        $jstr .='        method: "POST",'."\r\n";
        $jstr .='        data:pb'."\r\n";
        $jstr .='    }).then((result) =>{'."\r\n";
        $jstr .='        $scope.berror = result.data.msg;'."\r\n";
        $jstr .='    }, function(error){'."\r\n";
        $jstr .='        $scope.berror = error.statusText;'."\r\n";
        $jstr .='    });'."\r\n".'}'."\r\n\r\n";
        return $jstr;
    }

    private static function get_js_delete($tbl){
        $tb = "";
        if ($tbl[strlen($tbl)-1] == "s")
            $tb = substr($tbl, 0, strlen($tbl)-1);
        else
            $tb = $tbl;
        $dstr = 'function delete_'.$tb.'($scope, $http, column, value){'."\r\n";
        $dstr .='    $http({'."\r\n";
        $dstr .='        url: base_api_url+"'.$tbl.'_http_api.php",'."\r\n";
        $dstr .='        method: "POST",'."\r\n";
        $dstr .='        data:{"method":"delete", "table":"'.$tbl.'", "data":';
        $dstr .='{"col_name":column, "col_value":value}}';
        $dstr .='    }).then((result) =>{'."\r\n";
        $dstr .='        $scope.berror = result.data.msg;'."\r\n";
        $dstr .='    }, function(error){'."\r\n";
        $dstr .='        $scope.berror = error.statusText;'."\r\n";
        $dstr .='    });'."\r\n".'}'."\r\n\r\n";
        return $dstr;
    }
}