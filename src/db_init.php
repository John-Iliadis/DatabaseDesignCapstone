<?php 

/* Init ------------------------------------------------------------------------- */
$servername = "localhost";
$username = "root";
$password = "";

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error)
{
	die("Connection failed: " . $conn->connect_error);
}


/* Classes ------------------------------------------------------------------------- */
class Archer
{
	public $id;
	public $age;
	public $gender;

	public function __construct($id, $age, $gender)
	{
		$this->id = $id;
		$this->age = $age;
		$this->gender = $gender;
	}

}


class Category
{
	public $name;
	public $age_min;
	public $age_max;
	public $gender;

	public function __construct($name, $age_min, $age_max, $gender)
	{
		$this->name = $name;
		$this->age_min = $age_min;
		$this->age_max = $age_max;
		$this->gender = $gender;
	}

}


class Competition
{
	public $id;
	public $date;

	public function __construct($id, $date)
	{
		$this->id = $id;
		$this->date = $date;
	}

}


/* Functions ------------------------------------------------------------------------- */
function execute_multi_query(&$sql_script): void
{
	global $conn;

	if ($conn->multi_query($sql_script) !== TRUE)
	{
		die("Error executing SQL file: " . $conn->error);
	}

	while(mysqli_more_results($conn))
	{
   		mysqli_next_result($conn);
	}
}


function insert_class_record($age_min, $age_max, $class_name, $gender): void
{
	global $conn;

	$query = "insert into class (AgeLimitMin, AgeLimitMax, ClassName, Gender) values ($age_min, $age_max, '$class_name', '$gender')";
	$result = $conn->query($query);
	if (!$result)
	{
		die("Error: " . $conn->error);
	}
}


function vals_to_array(&$sql_object, $field): array
{
	$arr = array();

	while ($row = $sql_object->fetch_assoc())
	{
		$arr[] = $row[$field];
	}

	return $arr;
}


function get_category(&$archer, &$categories)
{
    $matching_categories = array();

	foreach ($categories as $c)
	{
		if ($archer->age >= $c->age_min && $archer->age <= $c->age_max && $archer->gender === $c->gender)
		{
			$matching_categories[] = $c->name;
		}
	}


	return get_random_array_element($matching_categories);
}

function get_archer_championship_category(&$archer, &$categories, &$championship_categories)
{
    $matching_categories = array();

    foreach ($categories as $c)
    {
        if ($archer->age >= $c->age_min && $archer->age <= $c->age_max && $archer->gender === $c->gender)
        {
            if (in_array($c->name, $championship_categories))
            {
                $matching_categories[] = $c->name;
            }
        }
    }

    if (empty($matching_categories))
    {
        return null;
    }

    return get_random_array_element($matching_categories);
}

function get_random_scores(): array
{
	$scores = array();

	for ($i=0; $i < 6; $i++)
	{
		$scores[] = rand(0, 10);
	}

	rsort($scores);

	return $scores;
}


function get_random_date($start_date = '1945-01-01', $end_date = '2024-05-15'): string
{
	$startDate = strtotime($start_date);
	$endDate = strtotime($end_date);
	$randomTimestamp = mt_rand($startDate, $endDate);
	return date('Y-m-d', $randomTimestamp);
}


function get_random_array_element(&$arr)
{
	return $arr[array_rand($arr)];
}


function create_round_result_record($archer_id, $category_code, $round_code, $comp_id, $result_date): int
{
	global $conn;

    $archer_id = (int)$archer_id;

	$insert_query = "insert into roundresult (ArcherID, RoundCode, CategoryName, CompetitionID, Result, ResultDate)
                  values ($archer_id, '$round_code', '$category_code', $comp_id, NULL, '$result_date')";

	$result = $conn->query($insert_query);
	if (!$result)
	{
		die("create_round_result_record() query failed: " . $conn->error);
	}

	$result_id_query = "select * from roundresult order by roundresultID desc limit 1";
	$result = $conn->query($result_id_query);
	if (!$result)
	{
		die("create_round_result_record() query failed: " . $conn->error);
	}

	return (int)$result->fetch_assoc()["RoundResultID"];
}


function create_round_scores(int $round_result_id, &$round_code, &$rounds): void
{
	global $conn;

	$ranges = $rounds[$round_code];
	$round_result = 0;
	# iterate each range in the round
	for ($range_index = 1; $range_index <= count($ranges); $range_index++)
	{
		$end_count = $ranges[$range_index - 1];

		# iterate each end
		for ($end_index = 1; $end_index <= (int)$end_count; $end_index++)
		{
			$scores = get_random_scores();
			$round_result += array_sum($scores);

			$query = "insert into Score (RoundResultID, RangeIndex, EndIndex, Arrow1, Arrow2, Arrow3, Arrow4, Arrow5, Arrow6)
                      values ($round_result_id, $range_index, $end_index, $scores[0], $scores[1], $scores[2], $scores[3], $scores[4], $scores[5])";

			$result = $conn->query($query);
			if (!$result)
			{
				die("create_round_scores() query failed: " . $conn->error);
			}
		}
	}

	$update_round_result_query = "update roundresult set Result = $round_result where roundresultid = $round_result_id";
	$result = $conn->query($update_round_result_query);
	if (!$result)
	{
		die("create_round_scores() query failed: " . $conn->error);
	}
}

/* Create archerydb ------------------------------------------------------------------------- */
$create_archery_db_query =
"drop database if exists archerydb; create database archerydb;";

execute_multi_query($create_archery_db_query);

$conn = new mysqli($servername, $username, $password, "archerydb");

if ($conn->connect_error)
{
	die("Connection failed: " . $conn->connect_error);
}

set_time_limit(1800);

echo "Connected successfully<br>";


/* SQL Files ------------------------------------------------------------------------- */
$create_tables = file_get_contents("create_tables.sql");
$archer_sql = file_get_contents("archer.sql");
$competition_sql = file_get_contents("competition.sql");
$championship_sql = file_get_contents("championship.sql");
$round_sql = file_get_contents("round.sql");
$range_sql = file_get_contents("range.sql");
$class_sql = file_get_contents("class.sql");
$category_sql = file_get_contents("category.sql");
$equivalent_round_sql = file_get_contents("equivalentround.sql");
$round_category_sql = file_get_contents("roundcategory.sql");


/* Create Tables ------------------------------------------------------------------------- */
execute_multi_query($create_tables);


/* Archer Table ------------------------------------------------------------------------- */
execute_multi_query($archer_sql);

/* Championship Table ------------------------------------------------------------------------- */
execute_multi_query($championship_sql);


/* Competition Table ------------------------------------------------------------------------- */
execute_multi_query($competition_sql);


/* Round Table ------------------------------------------------------------------------- */
execute_multi_query($round_sql);


/* Range Table ------------------------------------------------------------------------- */
execute_multi_query($range_sql);


/* Class Table ------------------------------------------------------------------------- */
execute_multi_query($class_sql);


/* Categorty Table ------------------------------------------------------------------------- */
execute_multi_query($category_sql);


/* Equivalent Round Table ------------------------------------------------------------------------- */
execute_multi_query($equivalent_round_sql);


/* Round Category Table ------------------------------------------------------------------------- */
execute_multi_query($round_category_sql);


/* RoundResult and Score Basic Records ------------------------------------------------------------ */
$archer_query = "select ArcherID, ArcherAge, Gender from archer";
$rounds_query = "select * from `range`";

$categories_query =
"select cat.categoryname, cl.agelimitmin, cl.agelimitmax, cl.gender
from category cat
inner join class cl on cat.ClassName = cl.ClassName";

$round_categories_query = "select * from roundcategory";

$archer_records = $conn->query($archer_query);
$round_records = $conn->query($rounds_query);
$category_records = $conn->query($categories_query);
$round_categories_records = $conn->query($round_categories_query);

if (!$archer_records || !$category_records || !$round_records || ! $round_categories_records)
{
	die("Error: " . $conn->error);
}

$archers = array();
$rounds = array();
$categories = array();
$categories_rounds = array();
$round_categories = array();


while ($row = $archer_records->fetch_assoc())
{
	$archers[] = new Archer($row["ArcherID"], $row["ArcherAge"], $row["Gender"]);
}

while ($row = $round_records->fetch_assoc())
{
	$round_code = $row["RoundCode"];
	$end_count = $row["EndCount"];

	if (array_key_exists($round_code, $rounds))
	{
		$rounds[$round_code][] = $end_count;
	}
	else
	{
		$rounds[$round_code] = array($end_count);
	}
}

while ($row = $category_records->fetch_assoc())
{
	$categories[] = new Category($row["categoryname"], $row["agelimitmin"], $row["agelimitmax"], $row["gender"]);
}

while ($row = $round_categories_records->fetch_assoc())
{
	$category_name = $row["CategoryName"];
	$round_code = $row["RoundCode"];

	if (array_key_exists($category_name, $categories_rounds))
	{
		$categories_rounds[$category_name][] = $round_code;
	}
	else
	{
		$categories_rounds[$category_name] = array($round_code);
	}

    if (array_key_exists($round_code, $round_categories))
    {
        $round_categories[$round_code][] = $category_name;
    }
    else
    {
        $round_categories[$round_code] = array($category_name);
    }
}

$round_keys = array_keys($rounds);

# create a championship for testing
$championship_round = 'Melbourne';
$championship_categories = $round_categories[$championship_round];
$competition_id = 4;
$competition_date = '2023-01-27';

foreach ($archers as $archer)
{
	$archer_birth_date = date("Y-m-d", strtotime("-$archer->age years"));

	# create 20 records for each archer
	for ($i = 0; $i < 20; $i++)
	{
        $archer_category = get_category($archer, $categories);
        $result_date = get_random_date($archer_birth_date);
		$round_code = get_random_array_element($categories_rounds[$archer_category]);
		
		$round_result_id = create_round_result_record($archer->id, $archer_category, $round_code, "NULL", $result_date);

		create_round_scores($round_result_id, $round_code, $rounds);
	}

    $champ_category = get_archer_championship_category($archer, $categories, $championship_categories);

    if ($champ_category)
    {
        $round_result_id = create_round_result_record($archer->id, $champ_category, $championship_round, $competition_id, $competition_date);
        create_round_scores($round_result_id, $championship_round, $rounds);
    }
}


/* Terminate ------------------------------------------------------------------------- */
$conn->close();

echo "EXIT_SUCCESS<br>";
