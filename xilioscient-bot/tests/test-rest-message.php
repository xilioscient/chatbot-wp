<?php
// Esempio di test PHPUnit per endpoint message
class Xilio_Rest_Message_Test extends WP_UnitTestCase {
    public function test_message_endpoint_requires_message() {
        $request = new WP_REST_Request( 'POST', '/mlc/v1/message' );
        $request->set_body_params( array() );
        $response = rest_do_request( $request );
        $this->assertEquals( 400, $response->get_status() );
    }

    public function test_message_endpoint_rate_limit() {
        // Simula molte richieste dallo stesso IP
        for ($i=0;$i<35;$i++){
            $request = new WP_REST_Request( 'POST', '/mlc/v1/message' );
            $request->set_body_params( array( 'message' => 'ciao' ) );
            $response = rest_do_request( $request );
        }
        $this->assertTrue( in_array( $response->get_status(), array(200,429) ) );
    }
}
