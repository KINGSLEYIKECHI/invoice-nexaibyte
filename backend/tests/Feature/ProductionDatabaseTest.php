<?php
it('uses the MySQL test database when CI requests production parity',function(){
 if(getenv('EXPECT_MYSQL')!=='1'){$this->markTestSkipped('MySQL CI guard only');}
 expect(config('database.default'))->toBe('mysql');
 expect(config('database.connections.mysql.database'))->toStartWith('invoice_ci');
});
