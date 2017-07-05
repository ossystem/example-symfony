Bundle for integration sphinx search engine with symfony progect.
Usage. 
Register SphinxsearchBundle and configure mapping for doctrine entity to sphinx index as bellow:

sphinxsearch:
    searchd:
        host:   %sphinxsearch_host%
        port:   %sphinxsearch_port%
        socket: %sphinxsearch_socket%
    ql:
        host:   %sphinxql_host%
        port:   %sphinxql_port%
        socket: %sphinxql_socket%
    indexer:
        bin:    %sphinxsearch_indexer_bin%
    indexes:
        - name: ProektRishennyaDepKom   #Index label
          index:                        #list of indexes
              - ProektRishennyaDepKom
              - ProektRishennyaDepKom_rt
          field_weights:
              num: 10
              title: 1
              brief: 1
    mapping:        
        ProektRishennyaDepKom:
           repository: "AppBundle:Doc\\ProektRishennyaDepKom" #Doctrine repository name
           parameter: "repo"                                  #returned by sphinx. By this parameter Bundle will choose repository
           value: 3                                           #uniq value for parameter
           rt_fields:
                id:                 {type:id,             map:id}
                date_of:            {type:attr_timestamp, map:dateOf}
                title:              {type:field,          map:title}
                brief:              {type:field,          map:brief}                
                created_at:         {type:attr_timestamp, map:createdAt}
                updated_at:         {type:attr_timestamp, map:updatedAt}
           rt_name: ProektRishennyaDepKom_rt
           delete_attr: deleted

Then generate config understood by sphix using command (use ondisk parameter to save ram)
#app/console sphinx:config:generate 
Run sphinx instance with generated config
#app/console sphinx:config:run


Bundle keep modification of entities in rt indexes, so search will always on actual data. 
To reindex and clear all rt indexes use command:
#app/console sphinx:indexer 

Use search as below:

  $sphinxSearch = $this->get('search.sphinxsearch.search');
  $sphinxSearch->setFilter('deleted', array(0));
  $sphinxSearch->SetFilterRange('date_of', 1483228800, 1514764800);
  $sphinxSearch->setSortMode(SPH_SORT_EXTENDED, 'date_of DESC, @id DESC');
  $searchResults = $sphinxSearch->search(
      'search phrase',
      array(
          'ProektRishennyaDepKom'
      )
  );
Bundle will ask in sphinx for id matched documents and extract Doctrine entities.
Also knp paginator intergation built in:
  $paginator = $this->get('knp_paginator');
  $entities = $paginator->paginate($searchResults->get(0));
   
