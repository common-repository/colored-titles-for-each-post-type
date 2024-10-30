<?php
/*
Plugin Name: Colored Titles for each Post Type
Plugin URI: http://sandaltechnologies.com/wpplugins/colored-post-type/
Description: Choose the Title color for each post types
Author: Shahid Ahmed
Version: 1.0
Author URI: http://sandaltechnologies.com/
*/
/*
    Copyright (C) 2010-12  Shahid Ahmed
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
new posttypestitlecolor();
class posttypestitlecolor
{
    var $post_types_to_avoid = array("nav_menu_item", "revision", "attachment");
    function __construct()
    {
        register_deactivation_hook(__FILE__, array($this,'posttypestitlecolor_uninstall'));
        register_activation_hook(__FILE__, array($this,'posttypestitlecolor_install'));

        add_action('admin_menu', array($this,'posttypestitlecolor_menu'));
        add_action('admin_footer', array($this, 'add_footer'));
        add_filter ( 'the_title', array($this, "apply_colors"), 10 );
    }
    function posttypestitlecolor_menu()
    {
        add_menu_page('Custom Title Colors', 'Custom Title Colors', 'administrator', 'posttypestitlecolor', array($this,'posttypestitlecolor_manage'));
    }
    function apply_colors($title)
    {
        global $post;
        if(strtolower($post->post_title) != strtolower($title))
        {
            return $title;
        }
        $save_colors = $this->get_post_type_colors();
        if ( isset( $save_colors[$post->post_type] ) ){
            $post_color = $save_colors[$post->post_type];
	    return "<span style='color:{$post_color};'>{$title}</span>";
        }
	return $title;
    }
    function save_color($post)
    {
        if(isset($post['post_types']) and isset($post['color']) )
        {
            global $wpdb;
            
            $post_types = $_POST['post_types'];
            $colors = $_POST['color'];
            
            $table_name = $wpdb->prefix ."posttypestitlecolor";
            
            for($i = 0; $i < count($colors) ; $i++)
            {
                $ptype = $post_types[$i];
                $pcolor = $colors[$i];
                
                $sql = "SELECT * FROM ".$table_name." WHERE custom_post_type = '$ptype' ";
                $results = $wpdb->get_results($sql);
                if(isset($results[0]->custom_post_type) and trim($results[0]->custom_post_type) == $ptype )
                {
                    $sql = 'Update ' . $table_name . " SET color = '$pcolor' WHERE custom_post_type = '$ptype' ;";
                }
                else
                {
                    $sql = 'INSERT INTO ' . $table_name . " (custom_post_type, color, exclude_posts, title_prefix, title_suffix) VALUES('$ptype', '$pcolor', '', '', '');";
                }
                $wpdb->query( $sql );
            }
        }
        else{
            return false;
        }
    }
    function posttypestitlecolor_manage()
    {
        if(isset($_POST['sbmt']))
        {
            $this->save_color($_POST['sbmt']);
        }
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
        
        $save_colors = $this->get_post_type_colors();
        
        $out = '<form method="post"><fieldset style="width:90%;border:1px solid #141378;">
        <legend style="color:#141378;">  Select Color for Each Post Type  </legend>
        <div style="width:100%;margin:10px 0 0 10px;">';
        
        $headcol1_style = 'style="width:15%;float:left;height:25px;border-bottom:1px solid #ccc;background-color:#ccc;"';
        $headcol2_style = 'style="width:70%;float:left;padding-left:20px;height:25px;border-bottom:1px solid #ccc;background-color:#ccc;"';
        
        $col1_style = 'style="width:15%;float:left;border-right:1px solid #ccc;height:33px;"';
        $col2_style = 'style="width:70%;float:left;padding-left:20px;padding-top:3px;"';
        
        $out .= '<div '.$headcol1_style.'><strong>POST TYPE</strong>';
        $out .= '</div>';
        
        $out .= '<div '.$headcol2_style.'>';
        $out .= '<strong>COLOR</strong>';
        $out .= '</div>';
        
        foreach ( get_post_types() as $k => $type ) :
            if(!in_array($type, $this->post_types_to_avoid))
            {
                $out .= '<div '.$col1_style.'><strong>'.$type.'</strong>';
                $out .= '</div>';
                
                $out .= '<div '.$col2_style.'>';
                $out .= '<input type="hidden" name="post_types[]" value="' . $type .'" />';
                if(isset($save_colors[ $type ]))
                {
                    $post_color = $save_colors[ $type ];
                }
                else
                {
                    $post_color = "#000000";
                }
                $out .= '<input type="text" class="colorpick" name="color[]" value="' . $post_color .'" />';
                $out .= '</div>';
                
            }
        endforeach;
        
        $out .= '<div '.$col1_style.'><strong></strong>';
        $out .= '</div>';
        
        $out .= '<div '.$col2_style.'>';
        $out .= '<input type="submit"  name="sbmt" value="Save" />';
        $out .= '</div>';
        
        $out .= '</div>
        
        </fieldset></form>';
        
        echo $out;
    }
    function add_footer()
    {
        $page = $_GET['page'];
        
        if($page == "posttypestitlecolor")
        {
            ?><script>
                jQuery(document).ready(function()
                {
                    jQuery('.colorpick').wpColorPicker({
                        change: function( event, ui ) {
                            jQuery(this).val( ui.color.toString() );
                        }
                    });
                });
            </script>
            <?php
        }
    }
    
    function get_post_type_colors()
    {
        global $wpdb;
        $table_name = $wpdb->prefix ."posttypestitlecolor";
        $sql = "SELECT * FROM ".$table_name."; ";
        $results = $wpdb->get_results($sql);
        $save_colors = array();
        foreach($results as $v)
        {
            $save_colors[$v->custom_post_type] = $v->color;
        }
        return $save_colors;
    }
    function posttypestitlecolor_install() {
        global $wpdb;
        global $posttypestitlecolor_db_version;
        $table_name = $wpdb->prefix ."posttypestitlecolor";
        $sql = " CREATE TABLE `$table_name` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `custom_post_type` varchar(50) CHARACTER SET utf8 NOT NULL,
                    `color` varchar(20) CHARACTER SET utf8 NOT NULL,
                    `exclude_posts` text CHARACTER SET utf8 NOT NULL,
                    `title_prefix` varchar(500) CHARACTER SET utf8 DEFAULT NULL,
                    `title_suffix` text CHARACTER SET utf8,
                    PRIMARY KEY (`id`)
                    ) ENGINE=MyISAM DEFAULT CHARSET=utf8";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        add_option('posttypestitlecolor_db_version', $posttypestitlecolor_db_version);
    }
    function posttypestitlecolor_uninstall() {
        global $wpdb;
        global $ccpo_db_version;
        $table_name = $wpdb->prefix ."posttypestitlecolor";
        $sql = "DROP TABLE IF EXISTS $table_name;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        delete_option('posttypestitlecolor_db_version');
    }
}//end class

