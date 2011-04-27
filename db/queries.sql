-- Sample Clearinghouse queries

-- Add a new user based on InCommon info
insert into ch_user (eppn) values ('jquser@example.com');

-- Add a new aggregate
insert into ch_person
        (name, email, telephone)
    VALUES
        ('John Q. Admin', 'jqadmin@example.edu', '555-1001');

-- maybe just use "currval(pg_serial...('tbl','col'))" as the
-- value in the next insert, and do all three in a transaction.

