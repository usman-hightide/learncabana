<?php
/*
Template Name: Custom User Table
*/
?>
<?php
get_header();

global $wpdb;

$items_per_page = 50;
$current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$offset = ($current_page - 1) * $items_per_page;


// Fetch unique verbs from the wp_uotincan_reporting table
$CourseProgresses = $wpdb->get_col("SELECT DISTINCT Verb FROM {$wpdb->prefix}uotincan_reporting");
// Fetch data from the user table
$users = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}users LIMIT 30" );
// Groups
$groups = $wpdb->get_results( "SELECT post_title FROM {$wpdb->prefix}posts WHERE post_type = 'groups' " );
//print_r($groups);
//exit;
// Fetch the reporting data along with user email
/* $verb_filter = isset($_GET['verbFilter']) && !empty($_GET['verbFilter']) ? $_GET['verbFilter'] : '';

if ($verb_filter) {
    $query = $wpdb->prepare(
        "
        SELECT r.*, u.user_email, u.user_login 
        FROM {$wpdb->prefix}uotincan_reporting r
        LEFT JOIN {$wpdb->prefix}users u ON r.user_id = u.ID
        WHERE r.verb = %s
        LIMIT %d OFFSET %d
        ",
        $verb_filter, $items_per_page, $offset
    );
} else {
    $query = $wpdb->prepare(
        "
        SELECT r.*, u.user_email, u.user_login
        FROM {$wpdb->prefix}uotincan_reporting r
        LEFT JOIN {$wpdb->prefix}users u ON r.user_id = u.ID
        LIMIT %d OFFSET %d
        ",
        $items_per_page, $offset
    );
}

$reports = $wpdb->get_results($query); */


$verb_filter = isset($_GET['verbFilter']) && !empty($_GET['verbFilter']) ? $_GET['verbFilter'] : '';
$user_status_filter = isset($_GET['userStatusFilter']) && !empty($_GET['userStatusFilter']) ? $_GET['userStatusFilter'] : '';

// Base query
$query = "
    SELECT r.*, u.*
    FROM {$wpdb->prefix}uotincan_reporting r
    LEFT JOIN {$wpdb->prefix}users u ON r.user_id = u.ID
    LEFT JOIN {$wpdb->prefix}usermeta um ON u.ID = um.user_id AND um.meta_key = 'user_status'
";

// Where conditions
$where_conditions = [];

if ($verb_filter) {
    $where_conditions[] = $wpdb->prepare("r.verb = %s", $verb_filter);
}

if ($user_status_filter) {
    $where_conditions[] = $wpdb->prepare("um.meta_value = %s", $user_status_filter);
}

// Append where conditions to the base query
if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= $wpdb->prepare(" LIMIT %d OFFSET %d", $items_per_page, $offset);

$reports = $wpdb->get_results($query);
//print_r($reports);
//exit;


// Base count query
$total_items_query = "SELECT COUNT(r.id) FROM {$wpdb->prefix}uotincan_reporting r
                      LEFT JOIN {$wpdb->prefix}users u ON r.user_id = u.ID
                      LEFT JOIN {$wpdb->prefix}usermeta um ON u.ID = um.user_id AND um.meta_key = 'user_status'";

// Append where conditions to the count query
if (!empty($where_conditions)) {
    $total_items_query .= " WHERE " . implode(" AND ", $where_conditions);
}

$total_items = $wpdb->get_var($total_items_query);
$total_pages = ceil($total_items / $items_per_page);



?>

<style>
.site-content .ast-container {
    display: flex;
    flex-direction: column;
}
.pagination {
        text-align: center;
        margin-top: 20px;
    }

    .pagination a, .pagination .current-page, .pagination .ellipses {
        display: inline-block;
        margin: 0 5px;
        padding: 5px 10px;
        border: 1px solid #ccc;
    }

    .pagination .current-page {
        background-color: #333;
        color: #fff;
    }

    .pagination .ellipses {
        border: none;
        vertical-align: middle;
	}
</style>

<h1>Custom User Table</h1>

<!-- Add this above the table in your PHP file -->
<input type="text" id="searchInput" placeholder="Search for usernames..." onkeyup="filterTable()">

<form method="GET" action="">
    <select name="verbFilter">
        <option value="">Show All</option>
        <?php foreach ($CourseProgresses as $CourseProgresse) : ?>
            <option value="<?php echo esc_attr($CourseProgresse); ?>" <?php selected($_GET['verbFilter'], $CourseProgresse); ?>>
                <?php echo esc_html($CourseProgresse); ?>
            </option>
        <?php endforeach; ?>
    </select>
	<!-- Active/Non-Active Dropdown -->
    <select name="userStatusFilter">
        <option value="">All Users</option>
        <option value="active" <?php selected($_GET['userStatusFilter'], 'active'); ?>>Active Users</option>
        <option value="non-active" <?php selected($_GET['userStatusFilter'], 'non-active'); ?>>Non-Active Users</option>
    </select>
	
		<!-- Active/Non-Active Dropdown -->
    <select name="userGroupFilter">
        <option value="">All Groups</option>
		<?php foreach ($groups as $group) :?>
		
            <option value="<?php echo esc_attr($group->post_title); ?>" <?php selected($_GET['userGroupFilter'], $group->post_title); ?>>
                <?php echo esc_html($group->post_title); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <input type="submit" value="Filter">
</form>


<script>
/* function filterTable() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('userTableBody');
    const tr = table.getElementsByTagName('tr');

    for (let i = 0; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td')[1]; // Assumes username is in the 2nd column
        if (td) {
            const txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
} */
</script>

<script>
function filterTableByVerb() {
    const selectedVerb = document.getElementById('verbFilter').value;
    const table = document.getElementById('reportTableBody');
    const tr = table.getElementsByTagName('tr');

    for (let i = 0; i < tr.length; i++) {
        const td = tr[i].getElementsByTagName('td')[1];
        if (td) {
            const txtValue = td.textContent || td.innerText;
            if (selectedVerb === "" || txtValue === selectedVerb) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
}
</script>


<table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>User Email</th>
            <th>Verb</th>
			<th>Status</th>
            <!-- Add more columns as needed -->
        </tr>
    </thead>
    <tbody id="reportTableBody">
        <?php foreach ($reports as $report) : ?>
		 
            <tr>
                <td><?php echo esc_html($report->id); ?></td>
                <td><?php echo esc_html($report->user_login); ?></td>
                <td><?php echo esc_html($report->verb); ?></td>
				<td><?php echo esc_html($report->user_status); ?></td>
                <!-- Add more columns as needed -->
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div class="pagination">
    <?php
    for ($i = 1; $i <= $total_pages; $i++) {
        // Display the first 3 pages and the last page
        if ($i <= 3 || $i == $total_pages) {
            if ($i == $current_page) {
                echo "<span class='current-page'>$i</span>";
            } else {
                echo "<a href='?paged=$i'>$i</a>";
            }
        }
        // Display ellipses after the first 3 pages but before the last page
        elseif ($i == 4 && $total_pages > 4) {
            echo "<span class='ellipses'>...</span>";
        }
    }
    ?>
</div>




<!-- <table border="1">
    <thead>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            
        </tr>
    </thead>
    <tbody id="userTableBody">
        <?php /* foreach ( $users as $user ) : ?>
            <tr>
                <td><?php echo $user->ID; ?></td>
                <td><?php echo $user->user_login; ?></td>
                <td><?php echo $user->user_email; ?></td>
               
            </tr>
        <?php endforeach; */ ?>
    </tbody>
</table>
 -->
<?php
get_footer();
