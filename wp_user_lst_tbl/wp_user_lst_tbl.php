<?php
/*
Plugin Name:  Simple Employees Table
Description: It displays a table with employee data
Author: Online Web Tutor
Author URI: https://onlinewebtutorblog.com/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: basic-wp-list-table
Version: 1.0
*/

// Loading table class
if (!class_exists('WP_List_Table')) {
      require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

// Extending class
class Employees_List_Table extends WP_List_Table
{
      private $users_data;

      private function get_users_data($search = "")
      {
            global $wpdb;

            if (!empty($search)) {
                  return $wpdb->get_results(
                        "SELECT ID,user_login,user_email,display_name from {$wpdb->prefix}users WHERE ID Like '%{$search}%' OR user_login Like '%{$search}%' OR user_email Like '%{$search}%' OR display_name Like '%{$search}%'",
                        ARRAY_A
                  );
            }else{
                  return $wpdb->get_results(
                        "SELECT ID,user_login,user_email,display_name from {$wpdb->prefix}users",
                        ARRAY_A
                  );
            }
      }

      // Define table columns
      function get_columns()
      {
            $columns = array(
                  'cb'            => '<input type="checkbox" />',
                  'ID' => 'ID',
                  'user_login' => 'Username',
                  'display_name'    => 'Name',
                  'user_email'      => 'Email'
            );
            return $columns;
      }

      // Bind table with columns, data and all
      function prepare_items()
      {
            if (isset($_POST['page']) && isset($_POST['s'])) {
                  $this->users_data = $this->get_users_data($_POST['s']);
            } else {
                  $this->users_data = $this->get_users_data();
            }

            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array($columns, $hidden, $sortable);

            /* pagination */
            $per_page = 2;
            $current_page = $this->get_pagenum();
            $total_items = count($this->users_data);

            $this->users_data = array_slice($this->users_data, (($current_page - 1) * $per_page), $per_page);

            $this->set_pagination_args(array(
                  'total_items' => $total_items, // total number of items
                  'per_page'    => $per_page // items to show on a page
            ));

            usort($this->users_data, array(&$this, 'usort_reorder'));

            $this->items = $this->users_data;
      }

      // bind data with column
      function column_default($item, $column_name)
      {
            switch ($column_name) {
                  case 'ID':
                  case 'user_login':
                  case 'user_email':
                        return $item[$column_name];
                  case 'display_name':
                        return ucwords($item[$column_name]);
                  default:
                        return print_r($item, true); //Show the whole array for troubleshooting purposes
            }
      }

      // To show checkbox with each row
      function column_cb($item)
      {
            return sprintf(
                  '<input type="checkbox" name="user[]" value="%s" />',
                  $item['ID']
            );
      }

      // Add sorting to columns
      protected function get_sortable_columns()
      {
            $sortable_columns = array(
                  'user_login'  => array('user_login', false),
                  'display_name' => array('display_name', false),
                  'user_email'   => array('user_email', true)
            );
            return $sortable_columns;
      }

      // Sorting function
      function usort_reorder($a, $b)
      {
            // If no sort, default to user_login
            $orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'user_login';
            // If no order, default to asc
            $order = (!empty($_GET['order'])) ? $_GET['order'] : 'asc';
            // Determine sort order
            $result = strcmp($a[$orderby], $b[$orderby]);
            // Send final sort direction to usort
            return ($order === 'asc') ? $result : -$result;
      }
}

// Adding menu
function my_add_menu_items()
{
      add_menu_page('Employees List Table', 'Employees List Table', 'activate_plugins', 'employees_list_table', 'employees_list_init');
}
add_action('admin_menu', 'my_add_menu_items');

// Plugin menu callback function
function employees_list_init()
{
      // Creating an instance
      $empTable = new Employees_List_Table();

      echo '<div class="wrap"><h2>Employees List Table</h2>';
      // Prepare table
      $empTable->prepare_items();
      ?>
            <form method="post">
                  <input type="hidden" name="page" value="employees_list_table" />
                  <?php $empTable->search_box('search', 'search_id'); ?>
            </form>
      <?php
      // Display table
      $empTable->display();
      echo '</div>';
}