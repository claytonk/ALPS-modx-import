<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/claytonk
 * @since      1.0.0
 *
 * @package    Modx_Import
 * @subpackage Modx_Import/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Modx_Import
 * @subpackage Modx_Import/admin
 * @author     Clayton Kinney <clayton@316creative.com>
 */
class Import_Post {

        public function __construct( $post ) {
                $this->post = $post['post'];
                $this->menu = $this->post['menu'];
                unset($this->post['menu']);
                $this->media = $post['media'];
                $this->errors = array();
                $this->result = array();
                $this->tags = $this->post['tags'];
                unset($this->post['tags']);
                $this->categories = $this->post['categories'];
                unset($this->post['categories']);

				// Check for specified header image. If it exists, import and set meta for header/hero
                if (!empty($this->media['featuredImage'])){
                        $featured = $this->media($this->media['featuredImage']);
                        if ($featured){
                             $this->post['meta_input']['_thumbnail_id'] = $featured->ID;
                             $this->post['meta_input']['_header_background_image'] = $featured->ID;
                             $this->post['meta_input']['_hero_image'] = $featured->ID;
                        }
                }

				// Check for linked media and import
                if ( !empty($this->media['links']) ){
                        $search = array();
                        $replace = array();
                        foreach($this->media['links'] as $i => $l){
                                $link = $this->media($l);
                                if ($link){
                                      $search[] = '[*'.$i.'*]';
                                      $replace[] = $link->guid;
                                }
                        }
                        if (!empty($search)){
                                $this->post['post_content'] = str_replace($search, $replace, $this->post['post_content']);
                        }
                }

				// Check for static resource file and import
                if (!empty($post['file'])){
                        $this->file = $this->media($post['file']);
                }

				// process import and menu inclusion
                if (!empty($this->post['post_content'])){
                        if ($postObject = get_page_by_title($this->post['post_title'],OBJECT,$this->post['post_type'])){
                                $this->post['ID'] = $postObject->ID;
                        }
                        $this->id = wp_insert_post( $this->post, true );
                        $postObject = get_post($this->id);
                        if ($this->menu && !empty($this->menu)){
                                $mData = array(
                                    'menu-item-title' => $postObject->post_title,
                                    'menu-item-object-id' => $postObject->ID,
                                    'menu-item-object' => $postObject->post_type,
                                    'menu-item-status' => 'publish',
                                    'menu-item-type' => 'post_type',
                                );
                                if ($this->menu[1] > 0){
                                        $mData['menu-item-parent-id'] = $this->menu[1];
                                }
                                $this->menuItem = wp_update_nav_menu_item(3, 0, $mData);
                        }else{
                                $this->menuItem = false;
                        }
                }

				// check for tag data and import
                if (!empty($this->tags) && !empty($this->id)){
                        $existing = $existing = wp_get_post_tags( $this->id, array( 'fields' => 'names' ) );
                        $tags = array();
                        foreach($this->tags as $tag){
                                if (!in_array($tag, $existing)){
                                        $tags[] = $tag;
                                }
                        }
                        $this->tagResult = wp_set_post_tags($this->id,$tags,true);
                }

				// check for category data and import
                if (!empty($this->categories) && !empty($this->id)){
                        $cats = array();
                        foreach($this->categories as $c){
                                $cSlug = strtolower(str_replace(' ', '-', $c));
                                if ($cat = get_category_by_slug(strtolower(str_replace(' ', '-', $c)))){
                                       $catID = $cat->term_id;
                                }else{
                                        $catID = wp_create_category($c,0);
                                }
                                if (!in_array($catID, $existing)){
                                        $cats[] = $catID;
                                }
                        }
                        $this->categoryResults = wp_set_post_categories($this->id,$cats,true);
                }

        }

        private function media($m){
                $file_array  = [ 'name' => wp_basename( $m['file'] ), 'tmp_name' => download_url( $m['file'] ) ];
                if ( is_wp_error( $file_array['tmp_name'] ) ) {
                        $this->errors['file error'] = $file_array['tmp_name'];
                }
                $name = sanitize_title(pathinfo($m['file'], PATHINFO_FILENAME));
                $mediaObject = get_page_by_title($name,OBJECT,'attachment');
                if (empty($mediaObject)){
                        $id = media_handle_sideload( $file_array, 0, $m['description'] );
                        if ($id){
                               $mediaObject = get_post($id);
                        }else{
                                $mediaObject = false;
                        }
                }
                $this->result['media'][] = $mediaObject;
                return $mediaObject;
        }

        /**
         * Register the stylesheets for the admin area.
         *
         * @since    1.0.0
         */

}
