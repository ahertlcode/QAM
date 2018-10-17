<?php


class apimaker
{
    static protected $exception;
    static protected $db_config;
    static protected $base_dir;

    public static function makeapi($params)
    {
        self::$exception = (isset($params[1]))? explode(",",str_replace("--exclude=","",$params[1])):"";
        $dbconfig = explode(",",str_replace("--db=","",$params[0]));
        for ($i = 0; $i < sizeof($dbconfig); $i++){
            $tpair = explode(":",$dbconfig[$i]);
            self::$db_config[$tpair[0]] = $tpair[1];
        }

        self::$base_dir = "../".self::$db_config["db"];
        self::writeconfig(self::$db_config);
        self::getTables();
    }

    private static function getTables(){
        global $dbname;
        $db = new DbHandlers();
        $tables = $db->show_dbTables($dbname);
        self::make_readme($tables, self::$db_config);
        self::makemodel($tables);
        self::spool_api($tables);
    }

    private static function make_readme($tables, $dbsettings){
        $readme = " HTTP RESTful API ".$dbsettings["db"]." Readme file.\n";
        $readme .= "==========================================================\n\n";
        $readme .= "# ".$dbsettings["db"]." Objects\n";
        $i = 1;
        foreach($tables as $ky => $tb){
            $readme .= "  ## ".$i.", ".$tb["Tables_in_".$dbsettings["db"]]."\n";
            $i += 1;
        }
        $readme .= "  ## Preamble\n  ### The solution implements the objects model from which the ";
        $readme .= "the various API is implemented. Following a modular application programming ";
        $readme .= "structure the API do not talk directly to the database, this apart from been ";
        $readme .= "modular prevent your application database from exploitation and possible attack.\n";
        $readme .= "  ### This method of doing things also prevent sql injection, the API does not accept ";
        $readme .= "sql query as the model makes it query internally, escape unwanted sequence and character set ";
        $readme .= "using PHP Data Object(PDO) for SQL and database operation, this ensure that all generated queries ";
        $readme .= "are prepared which eliminated submiting malicious code to the database server.";
        utilities::writetofile($readme, self::$base_dir."/", "Readme", "md");
    }

    private static function writeconfig($conf){
        $config = "<?php\n\n    //Database configuration file.\n";
        foreach($conf as $ky => $vl){
            $config .= "    $".$ky." = ".'"'.$vl.'"'.";\n";
        }
        $config .= "\n\n?>";
        utilities::writetofile($config, self::$base_dir."/","dbconfig", "php");
        self::copy_assets();

    }

    private static function copy_assets(){
        if(!file_exists(self::$base_dir.'/classes'))
        {
            mkdir(self::$base_dir.'/classes', 0777, true);
            mkdir(self::$base_dir.'/api', 0777, true);
            mkdir(self::$base_dir.'/logs/error', 0777, true);
        }
        utilities::xcopy('server', self::$base_dir.'/classes');
        utilities::xcopy('server/error', self::$base_dir.'/logs/error');
    }

    private static function create_class($tables){
        foreach ($tables as $tb){
            if ( self::exempted($tb) === false ){
                self::do_php_class($tb);
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

    private static function do_php_class($table){
        $dbf = self::dataObj();
        $tbl = $table["Tables_in_".$dbf->dbname];
        $phpcode = "<?php\r\n";
        $phpcode .= self::get_class_docstring();
        $phpcode .= "\r\n".self::get_class_properties($table);
        $phpcode .= "\r\n\r\n".self::get_class_constructor();
        $phpcode .= "\r\n\r\n".self::get_class_method_create($table);
        $phpcode .= "\r\n\r\n".self::get_Class_method_readAll($table);
        $phpcode .= "\r\n\r\n".self::get_Class_method_readOne($table);
        $phpcode .= "\r\n\r\n".self::get_class_method_update($table);
        $phpcode .= "\r\n\r\n".self::get_class_method_delete($table);
        $phpcode .= "\r\n}";
        utilities::writetofile($phpcode, self::$base_dir."/classes/", $tbl, "php");
    }

    private static function dataObj(){
        $db = new DbHandlers();
        $db->servername = self::$db_config["host"];
        $db->dbname = self::$db_config["db"];
        $db->username = self::$db_config["user"];
        $db->password = self::$db_config["password"];
        return $db;
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

    private static function get_class_constructor(){
        $ccon = "    public function _construct(){\r\n";
        $ccon .= "        /** Todo, add code for system initialization here!*/ \r\n";
        $ccon.= "    }";
        return $ccon;
    }

    private static function get_class_method_create($table){
        $dbc = self::dataObj();
        $tbn = $table["Tables_in_".$dbc->dbname];
        $tpr = self::table_structure($table, $dbc);
        $savestr ="    public function create(){\r\n";
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

    private static function get_Class_method_readAll($tbv){
        $dbv = self::dataObj();
        $tb = $tbv["Tables_in_".$dbv->dbname];
        //$tpv = self::table_structure($tbv, $dbv);
        $viewstr = '    public function readAll() {'."\r\n";
        $viewstr .= '        $db = new DbHandlers();'."\r\n";
        $viewstr .= '        $sql = "SELECT * from '.$tb.' order by id DESC";'."\r\n;        }";
        $viewstr .= '        $datasource = $db->getRowAssoc($sql);'."\r\n";
        $viewstr .= '        return $datasource;'."\r\n";
        $viewstr .= '    }';
        return $viewstr;
    }

    private static function get_Class_method_readOne($tbv){
        $dbv = self::dataObj();
        $tb = $tbv["Tables_in_".$dbv->dbname];
        //$tpv = self::table_structure($tbv, $dbv);
        $viewstr = '    public function readOne($critcol=null, $critval=null) {'."\r\n";
        $viewstr .= '        $db = new DbHandlers();'."\r\n";
        $viewstr .= '        $sql = "SELECT * from '.$tb.' WHERE $critcol ='."'{".'$critval'."}'".'";'."\r\n";
        $viewstr .= '        $datasource = $db->getRowAssoc($sql);'."\r\n";
        $viewstr .= '        return $datasource;'."\r\n";
        $viewstr .= '    }';
        return $viewstr;
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
    
    private static function spool_api($tbs){}
}
