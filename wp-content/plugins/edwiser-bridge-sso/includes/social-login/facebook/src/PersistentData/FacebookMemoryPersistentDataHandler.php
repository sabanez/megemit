<?php
/**
 * Copyright 2017 Facebook, Inc.
 *
 * You are hereby granted a non-exclusive, worldwide, royalty-free license to
 * use, copy, modify, and distribute this software in source code or binary
 * form for use in connection with the web services and APIs provided by
 * Facebook.
 *
 * As with any software that integrates with the Facebook platform, your use
 * of this software is subject to the Facebook Developer Principles and
 * Policies [http://developers.facebook.com/policy/]. This copyright notice
 * shall be included in all copies or substantial portions of the software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 */
namespace Facebook\PersistentData;

/**
 * Class FacebookMemoryPersistentDataHandler
 *
 * @package Facebook
 */
class FacebookMemoryPersistentDataHandler implements PersistentDataInterface
{
    /**
     * @var array The session data to keep in memory.
     */
    protected $sessionData = [];

    /**
     * @inheritdoc
     */
    public function get( $key ) {
        global $eb_session_id;
        // get unique session id.
        $session_id = session_id();


        if ( empty( $session_id ) ) {
            $session_id = $eb_session_id;
        }

        $fb_session_data = maybe_unserialize( get_option( $session_id ) );

        if ( isset( $fb_session_data[ $key ] ) ) {
            return $fb_session_data[ $key ];
        }

        return false;
        // return isset($this->sessionData[$key]) ? $this->sessionData[$key] : null;
    }

    /**
     * @inheritdoc
     */
    public function set( $key, $value ) {
        global $eb_session_id;

        // get unique session id.
        $session_id = session_id();

        if ( empty( $session_id ) ) {
            $session_id = $eb_session_id;
        }

        // get option value and then update it.
        $fb_session_data         = maybe_unserialize( get_option( $session_id ) );
        $fb_session_data[ $key ] = $value;
        $fb_session_data         = serialize( $fb_session_data );

        // update option with key as session id and data as value
        update_option( $session_id, $fb_session_data );

        // $this->sessionData[$key] = $value;
    }
}
