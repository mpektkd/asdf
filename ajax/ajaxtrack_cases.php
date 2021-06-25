<?php
include '../DBConnection.php';

## Read value
$draw = $_POST['draw'];
$row = $_POST['start'];
$rowperpage = $_POST['length']; // Rows display per page
$columnIndex = $_POST['order'][0]['column']; // Column index
$columnName = $_POST['columns'][$columnIndex]['data']; // Column name
$columnSortOrder = $_POST['order'][0]['dir']; // asc or desc
// $searchValue = $_POST['search']['value']; // Search value

## Custom Field value
$group = $_POST['group'];
$vid = $_POST['vid'];
// $vid = 1628;
$start = substr($group,0,2);
$end = substr($group,-2,2);

## Custom Field value
$searchByStart = $_POST['searchByStart'];
$searchByEnd = $_POST['searchByEnd'];

# datatable column index  => database column name
$columns = array( 
      1 => 'LastName', 
      2 => 'FirstName',
      3 => 'Age', 
      4 => 'Gender',
      5 => 'BirthDate',
      6 => 'Number',
      7 => 'SINNumber',
      8 => 'Region',
      9 => 'EntryDatetime',
      10 => 'ExitDatetime'
   );

## Search 
$searchQuery = " ";

if($searchByStart != '' && ($searchByEnd != '')){
   $searchQuery .= " and ( EntryDatetime between '".$searchByStart .
                     "' and '".$searchByEnd."' ) ";
}
if(($searchByStart != '') && ($searchByEnd == '')){
   $searchQuery .= " and ( EntryDatetime >= '".$searchByStart."' ) ";
}
if(($searchByStart == '') && ($searchByEnd != '')){
   $searchQuery .= " and ( EntryDatetime <= '".$searchByEnd."' ) ";
}
if( !empty($group) ){
   $searchQuery.=" AND ( Age >= " . $start . " and Age <= " . $end . " ) ";
}
if( !empty($_POST['columns'][1]['search']['value']) ){
   $searchQuery.=" AND ( LastName like '%".$_POST['columns'][1]['search']['value']."%') ";    
}
if( !empty($_POST['columns'][2]['search']['value']) ){
   $searchQuery.=" AND  ( FirstName like '%".$_POST['columns'][2]['search']['value']."%') ";
}
if( !empty($_POST['columns'][3]['search']['value']) ){
   $searchQuery.=" AND ( Age >= '".$_POST['columns'][3]['search']['value']."') ";    
}
if( !empty($_POST['columns'][4]['search']['value']) ){
    $searchQuery.=" AND ( Gender = '".$_POST['columns'][4]['search']['value']."') ";    
 }
 if( !empty($_POST['columns'][5]['search']['value']) ){
   $searchQuery.=" AND  ( BirthDate like '%".$_POST['columns'][5]['search']['value']."%') ";
}
if( !empty($_POST['columns'][6]['search']['value']) ){
   $searchQuery.=" AND  ( Number like '%".$_POST['columns'][6]['search']['value']."%') ";
}
if( !empty($_POST['columns'][7]['search']['value']) ){
    $searchQuery.=" AND ( SINNumber = '".$_POST['columns'][7]['search']['value']."') ";    
 }
 if( !empty($_POST['columns'][8]['search']['value']) ){
   $searchQuery.=" AND ( Region like '%".$_POST['columns'][8]['search']['value']."%') ";    
}
if( !empty($_POST['columns'][9]['search']['value']) ){
   $searchQuery.=" AND ( EntryDatetime like '%".$_POST['columns'][9]['search']['value']."%') ";    
}
if( !empty($_POST['columns'][10]['search']['value']) ){
   $searchQuery.=" AND ( ExitDatetime = '%".$_POST['columns'][10]['search']['value']."%') ";    
}

## Fetch records
$qry = 'SELECT 
            BraceletId,
            idRegions,
            EntryDatetime,
            ExitDatetime
         from CustomerVisitRegions 
            where idCustomerVisitRegions=' . $vid;
// echo $qry;

$sel = mysqli_query($con,$qry);
$record = mysqli_fetch_assoc($sel);
$bid = $record['BraceletId'];
$rid = $record['idRegions'];
$entry = $record['EntryDatetime'];
$exit = $record['ExitDatetime'];

$qry = 'SELECT 

            LastName, 
            FirstName,
            Gender,
            Age,
            BirthDate,
            Number,
            SINNumber,
            concat(Description_Place, " ", RegionName, " ", Floor, "-Floor") as Region,
            EntryDatetime,
            ExitDatetime

         from CustomerVisitRegions as a
         join Regions as b on b.idRegions=a.idRegions
         join ActiveCustomerLiveToRooms as c on c.BraceletId=a.BraceletId
         join CustomerInfo as d on d.id=c.idCustomer
         where a.BraceletId!=' . $bid . '
            and a.idRegions=' . $rid . '
            and 
               (
                  (a.EntryDatetime between "' . $entry . '" and date_add("' . $exit . '", interval 1 hour))
                  or
                  (a.ExitDatetime between "' . $entry . '" and "' . $exit . '")
                  or
                  ("' . $entry . '" between a.EntryDatetime and a.ExitDatetime)
               )';
               // echo $qry;
## Total number of records without filtering
$qry1 = "SELECT count(*) as allcount from (".$qry.")as r";

$sel = mysqli_query($con,$qry1);
$records = mysqli_fetch_assoc($sel);
$totalRecords = $records['allcount'];

## Total number of records with filtering
$qry2 = "SELECT count(*) as allcount from (".$qry .")as r
        where 1 ";

$sel = mysqli_query($con,$qry2 . $searchQuery);
$records = mysqli_fetch_assoc($sel);
$totalRecordwithFilter = $records['allcount'];


$qry3 = "SELECT * from (".$qry .")as r
        where 1 ";

$empQuery = $qry3 . $searchQuery." order by EntryDatetime, ExitDatetime " . $columnSortOrder ." limit ".$row.",".$rowperpage;
$empRecords = mysqli_query($con, $empQuery);
$data = array();

while ($row = mysqli_fetch_assoc($empRecords)) {
   $data[] = array(
     "LastName"=> $row['LastName'],
     "FirstName"=>$row['FirstName'],
     "Age"=>$row['Age'],
     "Gender"=>$row['Gender'],
     "BirthDate"=>$row['BirthDate'],
     "Number"=>$row['Number'],
     "SINNumber"=>$row['SINNumber'],
     "Region"=>$row['Region'],
     "EntryDatetime"=>$row['EntryDatetime'],
     "ExitDatetime"=>$row['ExitDatetime'],
   );
}

## Response
$response = array(
  "draw" => intval($draw),
  "iTotalRecords" => $totalRecords,
  "iTotalDisplayRecords" => $totalRecordwithFilter,
  "aaData" => $data
);

echo json_encode($response);


