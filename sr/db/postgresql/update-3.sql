------
--- Modify the service registry to have both PA and SA point to SA URL/cert
------
update service_registry 
set service_url = (select service_url from service_registry 
    		  where service_type = 1), 
    service_cert = (select service_cert from service_registry 
    		 where service_type = 1)  
where service_type = 2;
