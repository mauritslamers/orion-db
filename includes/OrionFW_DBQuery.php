<?php
  
class OrionFW_DBQueryInfo{
  
  
         // the conditions array can contain special items like 'ids' and 'order'
         // but also fieldnames as key with a value or
         // of course a new OrionFW_DBQueryInfo object containing a subquery 
         // The fieldname can also have a comma separated list 
  public $tablename = "";
  public $fieldnamelist = ""; // comma separated string of values
  public $conditions = array();  
}  
  
  
class OrionFW_DBQuery{
  // a class to create a query based on conditions and table names 
  
   /*
   
   because the createQUery function can be called recursively, and keeping track of 
   changes in PHP cannot be handled by observers (Cocoa and SC are much more fun :) )
   we need a complicated array system to keep track of the changes to be able to
   put WHERE and AND in when necessary.
   
   It is mainly that difficult because we use separate functions to actually create the stuff
   The functions will set the _queryUnchanged flag to false themselves.
   Because of the multiple recursion levels, the flag is in an array and the createQuery function
   will automatically raise the _numberOfRecursions at every call
   
   */  

private $_numberOfRecursions = -1;
private $_queryUnchanged = array(array());


private function addValueInListQuery($key,$value){
   /// Function to create a "key in (value)" clause in a SQL query
   /// \param[in] $key The key (fieldname)
   /// \param[in] $value The value(s) of the fieldname
   /// \return a part of the query or nothing
   if(($key != "") && ($value != "")){
       if($this->_queryUnchanged[$this->_numberOfRecursions]){
          $returnQuery = " WHERE ";
          $this->_queryUnchanged[$this->_numberOfRecursions] = false;
       }
       else {
          $returnQuery = " AND ";  
       }
       $returnQuery .= cleansql($key) . " in (" . cleansql($value) . ")";
       return $returnQuery;
   }
}


private function addSingleKeyValueQuery($key,$value,$valueIsText = false){
  /// The function to create a single key value pair in a query (id=x)
  /// \param[in] $key The key (fieldname)
  /// \param[in] $value The value
  /// \param[in] $valueIsText Set whether the content of $value is text 
  /// \return A part of the query or nothing
   if(($key != "") && ($value != "")){
       if($this->_queryUnchanged[$this->_numberOfRecursions]){
          $returnQuery = " WHERE ";
          $this->_queryUnchanged[$this->_numberOfRecursions] = false;
       }
       else {
          $returnQuery = " AND ";  
       }
       $returnQuery .= cleansql($key) . "=";
       if($valueIsText){   
          $returnQuery .= "'" . cleansql($value) . "' ";
       }
       else {
          $returnQuery .= cleansql($value) . " ";         
       }
       return $returnQuery;
   }  
}


function createSelectQuery(OrionFW_DBQueryInfo $info){
   $this->_numberOfRecursions++;
   //print_r($info);
   if(($info->tablename) && ($info->fieldnamelist)){
      $query = "select ". cleansql($info->fieldnamelist) . " from " . cleansql($info->tablename); // setup the start
      $this->_queryUnchanged[$this->_numberOfRecursions] = true; // set the unchanged query flag true
      // next check for a conditions object
      if(property_exists($info,'conditions')){ // we need an object of the current table to check fieldnames
          $tempTable = eval("return new " . $info->tablename . "_class;");
          foreach($info->conditions as $key=>$value){ 
               // so we iterate through the conditions array, processing fieldnames if they fit 
               // the table we are creating the query for, and processing the specials order and ids if
               // they are not empty
            switch($key){
               case 'order': // do nothing
                  break; 
               case 'ids': // string with comma separated ids
                     // no check necessary as SC will give proper data
                  $query .= $this->addValueInListQuery('id',$value);
                  break;
               default: // default means we probably have a fieldname, which we can ignore
                        // if the fieldname is not a property of the object
                  if(property_exists($tempTable,$key)){ 
                      // check for the $value type, if it is a instance of OrionFW_DBQueryInfo, we need to
                      // make up the field name and make a recursive call to create the subquery
                      if($value instanceof OrionFW_DBQueryInfo){
                        // make recursive call
                        $tmpQuery = createQuery($value);
                        if($tmpQuery != false){
                           $query .= $this->addValueInListQuery($key,$tmpQuery);
                         } 
                      }
                      else {
                        // not an object, but a field name, so enter the key value pair
                        // check if value is a list of values or a single value
                        // if the type in the DB contains char or text, it is regarded as a single value 
                        // (no way to distinguish between a comma separating numerical values or a textual comma)
                        // no check for key, because key cannot be empty due to property_exist check
                        // no empty check for value, because condition can be that is has to be empty
                        
                        $valueFieldIsText = $tempTable->fieldIsText($key);
                        $commaPos = strpos($value,',');
                        
                        if($valueFieldIsText){
                            // don't look for a comma if $valueFieldIsText 
                            $query .= $this->addSingleKeyValueQuery($key,$value,$valueFieldIsText);                            
                        }
                        else {
                           if($commaPos){
                             //multiple result
                             $query .= $this->addValueInListQuery($key,$value); 
                           } else {
                              // single result
                              $query .= $this->addSingleKeyValueQuery($key,$value,$valueFieldIsText);  
                           }  
                        }
                      } // end if $value instanceof 
                  } // end if property_exists($tempTable,$key) 
                  // no else clause, as we can ignore unknown fields
                  break;              
            } 
          }
          if(property_exists($info->conditions,'order')){
            // add the order to the end of the query
            // check for field names?
            $query .= " ORDER BY " . cleansql($conditions->order);
          }
      }
      // if ready, return the query
      $this->_numberOfRecursions--;
      return $query;
   } else { 
      $this->_numberOfRecursions--;
      return false;
   }  
}




  } // end class
?>