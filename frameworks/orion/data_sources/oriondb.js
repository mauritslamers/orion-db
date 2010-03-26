// ==========================================================================
// Project:   Orion.Oriondb
// Copyright: ©2010 My Company, Inc.
// ==========================================================================
/*globals Orion */

/** @class

  (Document Your Data Source Here)

  @extends SC.DataSource
*/
Orion.OrionDB = SC.DataSource.extend(
/** @scope Orion.Oriondb.prototype */ {

  OrionDBHost: 'localhost:4020', 

  OrionDBBaseUrl: '/~maurits/OrionDB',

  OrionDBGetRequest: function(url,notifyobj,notifyfunc,store,query,storeKey){
     SC.Request.getUrl(url)
             .header("X_SPROUTCORE_VERSION","1.0b")
             .header("X_REQUESTED_WITH","SC1.0b")
             .set('isJSON', YES) 
             .notify(notifyobj, notifyfunc, { store: store, query: query, storeKey: storeKey })
             .send();
   },
  // ..........................................................
  // QUERY SUPPORT
  // 

  fetch: function(store, query) {

    // TODO: Add handlers to fetch data for specific queries.  
    // call store.dataSourceDidFetchQuery(query) when done.
    // get record type
    var rectype = query.get('recordType');
    if(rectype && query.isRemote()){
      console.log('Remote fetch called on OrionDB');
      var url = "http://" + this.OrionDBHost + this.OrionDBBaseUrl + "/" + rectype.prototype.OrionDBUrl;
      //console.log("Found url: " + url);  
      this.OrionDBGetRequest(url,this,this._handleFetch,store,query);
      return YES;
    }
    //console.log('Local fetch called on OrionDB');
    return NO; // return YES if you handled the query
  },
  
  
  _handleFetch: function(response, params){
     var store = params.store,
         query = params.query;
     
     if(SC.$ok(response)){
        //console.log("Reponse successfully received!");
        var rectype = query.get('recordType');
        // check OrionDB is actually returning records:
        var recordsInResponse = response.get('body').records;
        if(recordsInResponse && recordsInResponse.length>0){
           var storeKeys = store.loadRecords(rectype, recordsInResponse);
           // only load query results when query is not local
           if(query.isRemote()){
              store.loadQueryResults(query, storeKeys);
           } else store.dataSourceDidFetchQuery(query);
           // handle error 
        } else store.dataSourceDidErrorQuery(query); 
     } else store.dataSourceDidErrorQuery(query);
  },

  // ..........................................................
  // RECORD SUPPORT
  // 
  
  retrieveRecord: function(store, storeKey, id) {
    
    // TODO: Add handlers to retrieve an individual record's contents
    // call store.dataSourceDidComplete(storeKey) when done.
    console.log('retrieveRecord called');
    
    var rectype = SC.Store.recordTypeFor(storeKey);   
    if(rectype && id){
       // we need the id
       var baseUrl = "http://" + this.OrionDBHost + this.OrionDBBaseUrl + "/";
       var url = baseUrl + rectype.prototype.OrionDBUrl + "/" + id;    
       this.OrionDBGetRequest(url,this,this._handleRetrieveRecord,store,null,storeKey);
    } else return NO ; // return YES if you handled the storeKey
    
    return YES;
  },
  
  _handleRetrieveRecord: function(request, params){
     var store = params.store,
         query = params.query,
         storeKey = params.storeKey,
         response = request.response();
         
    if(SC.$ok(request)){
       store.dataSourceDidComplete(storeKey, response);
    }
    else {
       store.dataSourceDidError(storeKey, response);  
    }
  },
  
  createRecord: function(store, storeKey) {
    
    // TODO: Add handlers to submit new records to the data source.
    // call store.dataSourceDidComplete(storeKey) when done.
    
    return NO ; // return YES if you handled the storeKey
  },
  
  updateRecord: function(store, storeKey) {
    
    // TODO: Add handlers to submit modified record to the data source
    // call store.dataSourceDidComplete(storeKey) when done.

    return NO ; // return YES if you handled the storeKey
  },
  
  destroyRecord: function(store, storeKey) {
    
    // TODO: Add handlers to destroy records on the data source.
    // call store.dataSourceDidDestroy(storeKey) when done
    
    return NO ; // return YES if you handled the storeKey
  }
  
}) ;
