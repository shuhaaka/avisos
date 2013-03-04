<?php

/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.1.0
 *	LISENSE: FL43K5653W2I - http://www.flynax.com/license-agreement.html
 *	PRODUCT: Real Estate Classifieds
 *	DOMAIN: avisos.com.bo
 *	FILE: LISTINGS_BOX.INC.PHP
 *
 *	This script is a commercial software and any kind of using it must be 
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out 
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2013
 *	http://www.flynax.com/
 *
 ******************************************************************************/

if ( $_GET['q'] == 'ext' )
{
	// system config
	require_once( '../../../includes/config.inc.php' );
	require_once( RL_ADMIN_CONTROL .'ext_header.inc.php' );
	require_once( RL_LIBS .'system.lib.php' );

	if ( $_GET['action'] == 'update' )
	{
		$reefless -> loadClass( 'Actions' );

		$field = $rlValid -> xSql( $_GET['field'] );
		$value = $rlValid -> xSql( nl2br( $_GET['value'] ) );
		$key = $rlValid -> xSql( $_GET['key'] );
		$id = (int)$_GET['id'];

		if( $field == 'Side' || $field == 'Status' )
		{
			$updateData = array(
				'fields' => array(
					$field => $value
				),
				'where' => array(
					'Key' => 'listing_box_' . $id
				)
			);
			$rlActions -> updateOne( $updateData, 'blocks');
			exit;
		}
		else
		{
			$reefless -> loadClass('ListingsBox', null, 'listings_box');
			$field_replace = array($field, $value, $id);
			$dataContent = $rlListingsBox  -> checkContentBlock( false, $field_replace );
			$updateDatas= array(
				'fields' => array(
					'Content' => $dataContent
				),
				'where' => array(
					'Key' => 'listing_box_' . $id
				)
			);
			$rlActions -> updateOne( $updateDatas, 'blocks');
			
			$updateData = array(
				'fields' => array(
					$field => $value
				),
				'where' => array(
					'ID' => $id
				)
			);
			$rlActions -> updateOne( $updateData, 'listing_box');
			exit;
		}
	}

	// data read 
	$limit = (int)$_GET['limit'];
	$start = (int)$_GET['start'];

	$sql  = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Side`, `T2`.`Status`, `T3`.`Value` AS `name` ";
	$sql .= "FROM `". RL_DBPREFIX ."listing_box` AS `T1` ";
	$sql .= "LEFT JOIN `". RL_DBPREFIX ."blocks` AS `T2` ON CONCAT('listing_box_',`T1`.`ID`) = `T2`.`Key` ";
	$sql .= "LEFT JOIN `".RL_DBPREFIX."lang_keys` AS `T3` ON CONCAT('blocks+name+',`T2`.`Key`) = `T3`.`Key` AND `T3`.`Code` = '". RL_LANG_CODE ."' ";
	$sql .= "ORDER BY `T1`.`ID` DESC ";
	$sql .= "LIMIT {$start}, {$limit}";
	$data = $rlDb -> getAll( $sql );

	foreach ( $data as $key => $value )
	{
		$data[$key]['Status'] = $GLOBALS['lang'][$data[$key]['Status']];
		$data[$key]['Side'] = $GLOBALS['lang'][$data[$key]['Side']];
		$data[$key]['Type'] = $GLOBALS['lang']['listing_types+name+'.$data[$key]['Type']];
		$data[$key]['Box_type'] = $GLOBALS['lang']['listings_box_'.$data[$key]['Box_type']];
	}
	
	$count = $rlDb -> getRow( "SELECT FOUND_ROWS() AS `count`" );
	$output['total'] = $count['count'];
	$output['data'] = $data;

	$reefless -> loadClass( 'Json' );
	echo $rlJson -> encode( $output );
	unset( $output );
}
else
{
	$reefless -> loadClass('ListingsBox', null, 'listings_box');
	
	/* get box type */
	$box_types = array(
		'popular'=> $lang['listings_box_popular'],
		'recently_added'=> $lang['listings_box_recently_added'],
		'random'=> $lang['listings_box_random']
	);
	$rating = $rlDb -> getOne( 'ID',  "`Status` = 'active' AND `Key` = 'rating'" , 'plugins' );
	if($rating)
	{	
		$box_types['top_rating'] = $lang['listings_box_top_rating'];		
	}
	$rlSmarty -> assign_by_ref( 'box_types', $box_types );
	
	/* get type list*/
	$rlSmarty -> assign_by_ref( 'listing_types', $rlListingTypes -> types );
	
	if ($_GET['action'] == 'add' || $_GET['action'] == 'edit')
	{	
		/* get categories/section */
		$sections = $rlCategories -> getCatTree(0, false, true);
		$rlSmarty -> assign_by_ref( 'sections', $sections );
		
		/* get all languages */
		$allLangs = $GLOBALS['languages'];
		$rlSmarty -> assign_by_ref( 'allLangs', $allLangs );
		
		/* get pages list */
		$pages = $rlDb -> fetch( array('ID', 'Key'), array('Tpl' => 1), "AND `Status` <> 'trash' ORDER BY `Key`", null, 'pages' );
		$pages = $rlLang -> replaceLangKeys( $pages, 'pages', array( 'name' ), RL_LANG_CODE, 'admin' );
		$rlSmarty -> assign_by_ref( 'pages', $pages );
		
		$b_key = $rlValid -> xSql($_GET['block']);
		
		// get current block info
		$sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT `T1`.*, `T2`.`Tpl`,`T2`.`Side`,`T2`.`Sticky`, `T2`.`Cat_sticky`, `T2`.`Subcategories`, `T2`.`Category_ID`, `T2`.`Page_ID` ";
		$sql .= "FROM `". RL_DBPREFIX ."listing_box` AS `T1` ";
		$sql .= "LEFT JOIN `". RL_DBPREFIX ."blocks` AS `T2` ON CONCAT('listing_box_',`T1`.`ID`) = `T2`.`Key` ";
		$sql .= "WHERE `T2`.`Status` <> 'trash' AND `T1`.`ID` = '{$b_key}' ";
		$block_info = $rlDb -> getRow($sql);
		
		if ($_GET['action'] == 'edit' && !$_POST['fromPost'])
		{
			$_POST['id'] = $block_info['ID'];
			$_POST['status'] = $block_info['Status'];
			$_POST['side'] = $block_info['Side'];
			$_POST['tpl'] = $block_info['Tpl'];
			$_POST['unique'] = $block_info['Unique'];
			$_POST['type'] = explode(',',$block_info['Type']);
			$_POST['box_type'] = $block_info['Box_type'];
			$_POST['count'] = $block_info['Count'];
			$_POST['show_on_all'] = $block_info['Sticky'];
			$_POST['cat_sticky'] = $block_info['Cat_sticky'];
			$_POST['subcategories'] = $block_info['Subcategories'];
			$_POST['categories'] = explode(',', $block_info['Category_ID']);

			$m_pages = explode(',', $block_info['Page_ID']);
			foreach ($m_pages as $page_id)
			{
				$_POST['pages'][$page_id] = $page_id;
			}
			unset($m_pages);
			// get names
			$names = $rlDb -> fetch( array( 'Code', 'Value' ), array( 'Key' => 'blocks+name+listing_box_'.$b_key ), "AND `Status` <> 'trash'", null, 'lang_keys' );
			foreach ($names as $nKey => $nVal)
			{
				$_POST['name'][$names[$nKey]['Code']] = $names[$nKey]['Value'];
			}
		}
			
		if ( isset($_POST['submit']) )
		{
			$errors = array();
			
			/* check name */
			$f_name = $_POST['name'];
			
			foreach ($allLangs as $lkey => $lval )
			{
				if ( empty( $f_name[$allLangs[$lkey]['Code']] ) )
				{
					$errors[] = str_replace( '{field}', "<b>".$lang['name']."({$allLangs[$lkey]['name']})</b>", $lang['notice_field_empty']);
					$error_fields[] = "name[{$lval['Code']}]";
				}				
				$f_names[$allLangs[$lkey]['Code']] = $f_name[$allLangs[$lkey]['Code']];
			}
			
			/* check side */
			$f_side = $_POST['side'];
			
			if ( empty($f_side) )
			{
				$errors[] = str_replace( '{field}', "<b>\"".$lang['block_side']."\"</b>", $lang['notice_select_empty']);
				$error_fields[] = 'side';
			}
			
			/* check type */
			$f_type = $_POST['type'];
			
			if ( empty($f_type) )
			{
				$errors[] = str_replace( '{field}', "<b>\"".$lang['listing_type']."\"</b>", $lang['notice_field_empty']);
				$error_fields[] = 'type';
			}
			
			/* check type */
			$f_box_type = $_POST['box_type'];
			
			if ( empty($f_box_type) )
			{
				$errors[] = str_replace( '{field}', "<b>\"".$lang['block_type']."\"</b>", $lang['notice_select_empty']);
				$error_fields[] = 'box_type';
			}
			
			/* check type */
			$f_count = $_POST['count'];
			
			if ( empty($f_count) )
			{
				$errors[] = str_replace( '{field}', "<b>\"".$lang['listings_box_number_of_listing']."\"</b>", $lang['notice_field_empty']);
				$error_fields[] = 'count';
			}
			elseif ( $f_count > 30 && !empty($f_count) )
			{
				$errors[] = $lang['listings_box_more_listings'];
				$error_fields[] = 'count';
			}
			
			
			if( !empty($errors) )
			{
				$rlSmarty -> assign_by_ref( 'errors', $errors );
			}
			else 
			{
				/* add/edit action */
				if ( $_GET['action'] == 'add' )
				{					
					$data_block = array(
						'Type' => implode(',', $f_type),
						'Box_type' => $f_box_type,
						'Unique' => $_POST['unique'],
						'Count' => $_POST['count']
					);
					
					if ( $action_block = $rlActions -> insertOne( $data_block, 'listing_box' ) )
					{
						$id = mysql_insert_id();
						$f_key = 'listing_box_' . $id;
						
						// get max position
						$position = $rlDb -> getRow( "SELECT MAX(`Position`) AS `max` FROM `" . RL_DBPREFIX . "blocks`" );
					
						// write main, block information
						$data = array(
							'Key' => $f_key,
							'Status' => $_POST['status'],
							'Position' => $position['max']+1,
							'Side' => $f_side,
							'Type' => 'php',
							'Tpl' => $_POST['tpl'],
							'Readonly' => '1',
							'Page_ID' => implode(',', $_POST['pages']),
							'Category_ID' => implode(',', $_POST['categories']),
							'Subcategories' => empty($_POST['subcategories']) ? 0 : 1,
							'Sticky' => empty($_POST['show_on_all']) ? 0 : 1,
							'Cat_sticky' => empty($_POST['cat_sticky']) ? 0 : 1,
							'Plugin' => 'listings_box'
						);
						$check_field = array('type' => implode(',',$f_type), 'box_type' => $f_box_type, 'count' => $_POST['count'], 'unique' => $_POST['unique']);
						$data['Content'] = $rlListingsBox  -> checkContentBlock( $check_field );
						
						if ( $action = $rlActions -> insertOne( $data, 'blocks' ) )
						{
							// write name's phrases
							foreach ($allLangs as $key => $value)
							{
								$lang_keys[] = array(
									'Code' => $allLangs[$key]['Code'],
									'Module' => 'common',
									'Status' => 'active',
									'Key' => 'blocks+name+' . $f_key,
									'Value' => $f_name[$allLangs[$key]['Code']],
									'Plugin' => 'listings_box'
								);
							}

							$rlActions -> insert( $lang_keys, 'lang_keys' );
							
							$message = $lang['block_added'];
							$aUrl = array( "controller" => $controller );
						}
						else 
						{
							trigger_error( "Can't add new block (MYSQL problems)", E_WARNING );
							$rlDebug -> logger("Can't add new block (MYSQL problems)");
						}
					}
				}
				elseif ( $_GET['action'] == 'edit' )
				{
					$f_key = 'listing_box_' . $_POST['id'];
					
					$data_block = array(
						'fields' => array(
							'Type' => implode(',', $f_type),
							'Box_type' => $f_box_type,
							'Unique' => $_POST['unique'],
							'Count' => $_POST['count']
						),
						'where' => array( 'ID' => $_POST['id'] )
					);
					$GLOBALS['rlActions'] -> updateOne( $data_block, 'listing_box' );
					
					$update_data = array(
						'fields' => array(
							'Status' => $_POST['status'],
							'Side' => $f_side,
							'Tpl' => $_POST['tpl'],
							'Page_ID' => implode(',', $_POST['pages']),
							'Sticky' => empty($_POST['show_on_all']) ? 0 : 1,
							'Category_ID' => $_POST['cats_sticky'] ? '' : implode(',', $_POST['categories']),
							'Subcategories' => empty($_POST['subcategories']) ? 0 : 1,
							'Cat_sticky' => empty($_POST['cat_sticky']) ? 0 : 1
						),
						'where' => array( 'Key' => $f_key )
					);
					$check_field = array('type' => implode(',',$f_type), 'box_type' => $f_box_type, 'count' => $_POST['count'], 'unique' => $_POST['unique']);
					$update_data['fields']['Content']  = $rlListingsBox  -> checkContentBlock( $check_field );
					$action = $GLOBALS['rlActions'] -> updateOne( $update_data, 'blocks' );

					foreach ($allLangs as $key => $value)
					{
						if ( $rlDb -> getOne('ID', "`Key` = 'blocks+name+{$f_key}' AND `Code` = '{$allLangs[$key]['Code']}'", 'lang_keys') )
						{
							// edit name's values
							$update_names = array(
								'fields' => array(
									'Value' => $_POST['name'][$allLangs[$key]['Code']]
								),
								'where' => array(
									'Code' => $allLangs[$key]['Code'],
									'Key' => 'blocks+name+' . $f_key
								)
							);
							
							// update
							$rlActions -> updateOne( $update_names, 'lang_keys' );
						}
						else
						{
							// insert names
							$insert_names = array(
								'Code' => $allLangs[$key]['Code'],
								'Module' => 'common',
								'Key' => 'blocks+name+' . $f_key,
								'Value' => $_POST['name'][$allLangs[$key]['Code']]
							);
							// insert
							$rlActions -> insertOne( $insert_names, 'lang_keys' );
						}
					}

					$message = $lang['block_edited'];
					$aUrl = array( "controller" => $controller );
				}
				
				if ( $action )
				{
					$reefless -> loadClass( 'Notice' );
					$rlNotice -> saveNotice( $message );
					$reefless -> redirect( $aUrl );
				}
			}
		}
		
		
		$rlXajax -> registerFunction( array( 'getCatLevel', $rlCategories, 'ajaxGetCatLevel' ) );
		$rlXajax -> registerFunction( array( 'openTree', $rlCategories, 'ajaxOpenTree' ) );
	}
	$rlXajax -> registerFunction( array( 'deleteBoxBlock', $rlListingsBox, 'ajaxDeleteBoxBlock' ) );
}