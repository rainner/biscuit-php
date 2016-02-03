<?php
/**
 * For working with hostnames and IP addresses.
 *
 * @package    Biscuit PHP Framework
 * @author     Rainner Lins <http://rainnerlins.com/>
 * @copyright  (c) All Rights Reserved
 * @license    See included LICENSE file
 */
namespace Biscuit\Http;

class Dns {

	// input info
	protected $host   = '';
    protected $domain = '';
    protected $tld    = '';
	protected $ip     = '';

	/**
	 * Start a new class instance for a given hostname.
	 */
	public function __construct( $target='' )
	{
        $this->setTarget( $target );
	}

    /**
	 * Sets the hostname and ip based on a given target value (host/ip)
	 */
	public function setTarget( $target='' )
	{
		// check target param
        if( !empty( $target ) )
        {
            if( filter_var( $target, FILTER_VALIDATE_IP ) ){ $this->setIP( $target ); }
            else{ $this->setHost( $target ); }
        }
		return $this;
	}

	/**
	 * Sets a new hostname to work with.
	 */
	public function setHost( $value='' )
	{
		if( !empty( $value ) )
        {
            $this->host   = $this->_host( $value );
            $this->domain = $this->getHostDomain();
            $this->tld    = preg_replace( "/^.*\.([a-z]{2,8})$/i", "$1", $this->host );
        }
        return $this;
	}

	/**
	 * Sets a new IP address to work with.
	 */
	public function setIP( $value='' )
	{
		if( !empty( $value ) ) $this->ip = $this->_ip( $value );
		return $this;
	}

	/**
	 * Gets a DNS record array for the target host
	 */
	public function getDnsRecord()
	{
		if( !empty( $this->host ) && function_exists( 'dns_get_record' ) )
		{
			return dns_get_record( $this->host );
		}
		return array();
	}

	/**
	 * Get the IP (v4/v6) address for the target host
	 */
	public function getHostIP( $version=4 )
	{
		// try to get the DNS record for this hostname
        $rec = $this->getDnsRecord();
        $out = '';

		if( !empty( $rec ) )
        {
            foreach( $rec as $r )
            {
                // check required params and host name
                if( empty( $r['host'] ) || empty( $r['type'] ) || $r['host'] !== $this->host ) continue;

                // testing IPv4
                if( $version == 4 && $r['type'] === 'A' ){ $out = trim( $r['ip'] ); break; }

                // testing IPv6
                if( $version == 6 && $r['type'] === 'AAAA' ){ $out = trim( $r['ipv6'] ); break; }
            }
        }
		return $out;
	}

	/**
	 * Get the IP for the target host
	 */
	public function getHostIPv4()
	{
		return $this->getHostIP( 4 );
	}

	/**
	 * Get the IPv6 for the target host
	 */
	public function getHostIPv6()
	{
		return $this->getHostIP( 6 );
	}

	/**
	 * Get the domain name for the target host
	 */
	public function getHostDomain()
	{
		if( empty( $this->host ) ) return '';
        if( preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $this->host, $m ) ) return trim( $m['domain'] );
        return trim( preg_replace( "/^(.*\.)?([^.]*\..*)$/i", "$2", $this->host ) );
	}

	/**
	 * Get the hostname for the target IP
	 */
	public function getIpHostname()
	{
        if( empty( $this->ip ) ) return '';
        $host = gethostbyaddr( $this->ip );
        return !empty( $host ) ? trim( $host ) : '';
	}

	/**
	 * Get the domain name for the target IP
	 */
	public function getIpDomain()
	{
		$host = $this->getIpHostname();
        if( empty( $host ) ) return '';

        $this->setHost( $host );
		return $this->getHostDomain();
	}

    /**
     * Get geolocation info for the target IP
     */
    public function getGeo( $api='' )
    {
        // try to get an IP address, if none set
        if( empty( $this->ip ) ) $this->setIP( $this->getHostIP() );
        if( empty( $this->ip ) ) return array();

        // API URL
        $url = !empty( $api ) ? trim( $api ) : 'http://www.telize.com/geoip/';
        $url = $url . $this->ip;

		// send request and get response
		$c = curl_init();
		curl_setopt( $c, CURLOPT_URL, $url );
		curl_setopt( $c, CURLOPT_REFERER, $url );
		curl_setopt( $c, CURLOPT_USERAGENT, 'PHP/'.phpversion() );
		curl_setopt( $c, CURLOPT_CONNECTTIMEOUT, 10 );
		curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $c, CURLOPT_FAILONERROR, true );
		curl_setopt( $c, CURLOPT_FORBID_REUSE, true );
		curl_setopt( $c, CURLOPT_MAXREDIRS, 5 );
		$d = (array) json_decode( curl_exec( $c ), true );
		curl_close( $c );

        // default data to be available
        $out = array(
            'hostname' => $this->host,
            'domain'   => $this->domain,
            'tld'      => $this->tld,
            'ip'       => $this->ip
        );

        // return json decoded array
        if( is_array( $d ) )
        {
            unset( $d['ip'] );
            $out = array_merge( $out, $d );
        }
        return $out;
    }

    /**
	 * Get WHOIS data from a remote server, for a given hostname
	 */
	public function getWhois()
	{
        // try to get an IP address, if none set
        if( empty( $this->ip ) ) $this->setIP( $this->getHostIP() );

        // stuff
        $timeout = 5;
        $errors  = array();
        $out     = '';

        // get list of whois servers for this TLD
        $servers = $this->_whoisServer( $this->tld );

        // decide on what will be sent in the request
        $query = !empty( $this->ip ) ? $this->ip : $this->domain;

        // loop list of servers
        foreach( $servers as $server )
        {
            // connect to current server
            $fsock = fsockopen( $server, 43, $errno, $errstr, $timeout );
            $tmp   = '';

            // send the IP of target hostname
            fwrite( $fsock, $query . "\r\n" );

            // add response string to tmp var
            while( !feof( $fsock ) ) $tmp .= fgets( $fsock );

            // check error and close
            if( !empty( $errstr ) ) $errors[] = trim( $errstr );
            fclose( $fsock );

            // keep response string only if it's larger than previous one
            $tmp = $this->_parseOutput( $tmp );
            if( strlen( $tmp ) > strlen( $out ) ) $out = $tmp;

            // delay
            usleep( 500000 );
        }

        // return detailed output
        return array(
            'status'   => ( count( $errors ) || empty( $out ) ) ? 'failed' : 'success',
            'errors'   => trim( implode( ", ", $errors ) ),
            'servers'  => trim( implode( ", ", $servers ) ),
            'hostname' => $this->host,
            'ip'       => $this->ip,
            'tld'      => $this->tld,
            'timeout'  => $timeout,
            'whois'    => $out
        );
	}

	/**
	 * Clean a hostname value
	 */
	private function _host( $value='' )
	{
		$value = trim( $value );
		$value = preg_replace( '/^([a-zA-Z0-9\-]+\:)?\/\//', '', $value ); // remove protocol
		$value = preg_replace( '/(\:[0-9]+)$/', '', $value ); // remove port suffix
		$value = preg_replace( '/[^a-zA-Z0-9\.\-]+/', '', $value ); // remove other chars
		return $value;
	}

	/**
	 * Clean an IP value
	 */
	private function _ip( $value='' )
	{
		$value = trim( $value );
		$value = preg_replace( '/[^a-zA-Z0-9\.\:\/]+/', '', $value ); // normalize
		$value = preg_replace( '/(\/[0-9]{2})$/', '', $value ); // remove extension suffix
		return $value;
	}

    /**
     * Cleans up the WHOIS returned data
     */
    private function _parseOutput( $data='' )
    {
        $out  = '';
        $data = strip_tags( html_entity_decode( trim( $data ) ) );

        foreach( explode( "\n", $data ) as $line )
        {
            $line = trim( $line );

            // skipped commented lines
            if( preg_match( "/^(\<|\%|\#|\-)/i", $line ) ) continue;

            // exclude notice and terms from bottom
            if( preg_match( "/^(NOTICE|TERMS\ OF\ USE)\:/i", $line ) ) break;

            // add rest to output
            $out .= $line . "\n";
        }

        // remove multiple lines
        $out = preg_replace( "/\n\n+/i", "\n\n", $out );
        return trim( $out );
    }

    /**
     * Get a WHOIS server for TLD
     */
    private function _whoisServer( $tld='', $default='whois.arin.net' )
    {
        $l = array(
            "com"     =>  array( "whois.arin.net" ),
            "net"     =>  array( "whois.arin.net" ),
            "org"     =>  array( "whois.pir.org", "whois.publicinterestregistry.net" ),
            "info"    =>  array( "whois.afilias.info", "whois.afilias.net" ),
            "biz"     =>  array( "whois.neulevel.biz" ),
            "us"      =>  array( "whois.nic.us" ),
            "uk"      =>  array( "whois.nic.uk" ),
            "ca"      =>  array( "whois.cira.ca" ),
            "tel"     =>  array( "whois.nic.tel" ),
            "ie"      =>  array( "whois.iedr.ie", "whois.domainregistry.ie" ),
            "it"      =>  array( "whois.nic.it" ),
            "li"      =>  array( "whois.nic.li" ),
            "no"      =>  array( "whois.norid.no" ),
            "cc"      =>  array( "whois.nic.cc" ),
            "eu"      =>  array( "whois.eu" ),
            "nu"      =>  array( "whois.nic.nu" ),
            "au"      =>  array( "whois.aunic.net", "whois.ausregistry.net.au" ),
            "de"      =>  array( "whois.denic.de" ),
            "ws"      =>  array( "whois.worldsite.ws", "whois.nic.ws", "www.nic.ws" ),
            "sc"      =>  array( "whois2.afilias-grs.net" ),
            "mobi"    =>  array( "whois.dotmobiregistry.net" ),
            "pro"     =>  array( "whois.registrypro.pro", "whois.registry.pro" ),
            "edu"     =>  array( "whois.educause.net", "whois.crsnic.net" ),
            "tv"      =>  array( "whois.nic.tv", "tvwhois.verisign-grs.com" ),
            "travel"  =>  array( "whois.nic.travel" ),
            "name"    =>  array( "whois.nic.name" ),
            "in"      =>  array( "whois.inregistry.net", "whois.registry.in" ),
            "me"      =>  array( "whois.nic.me", "whois.meregistry.net" ),
            "at"      =>  array( "whois.nic.at" ),
            "be"      =>  array( "whois.dns.be" ),
            "cn"      =>  array( "whois.cnnic.net.cn", "whois.cnnic.cn" ),
            "asia"    =>  array( "whois.nic.asia" ),
            "ru"      =>  array( "whois.ripn.ru", "whois.ripn.net" ),
            "ro"      =>  array( "whois.rotld.ro" ),
            "aero"    =>  array( "whois.aero" ),
            "fr"      =>  array( "whois.nic.fr" ),
            "se"      =>  array( "whois.iis.se", "whois.nic-se.se", "whois.nic.se" ),
            "nl"      =>  array( "whois.sidn.nl", "whois.domain-registry.nl" ),
            "nz"      =>  array( "whois.srs.net.nz", "whois.domainz.net.nz" ),
            "mx"      =>  array( "whois.nic.mx" ),
            "tw"      =>  array( "whois.apnic.net", "whois.twnic.net.tw" ),
            "ch"      =>  array( "whois.nic.ch" ),
            "hk"      =>  array( "whois.hknic.net.hk" ),
            "ac"      =>  array( "whois.nic.ac" ),
            "ae"      =>  array( "whois.nic.ae" ),
            "af"      =>  array( "whois.nic.af" ),
            "ag"      =>  array( "whois.nic.ag" ),
            "al"      =>  array( "whois.ripe.net" ),
            "am"      =>  array( "whois.amnic.net" ),
            "as"      =>  array( "whois.nic.as" ),
            "az"      =>  array( "whois.ripe.net" ),
            "ba"      =>  array( "whois.ripe.net" ),
            "bg"      =>  array( "whois.register.bg" ),
            "bi"      =>  array( "whois.nic.bi" ),
            "bj"      =>  array( "www.nic.bj" ),
            "br"      =>  array( "whois.nic.br" ),
            "bt"      =>  array( "whois.netnames.net" ),
            "by"      =>  array( "whois.ripe.net" ),
            "bz"      =>  array( "whois.belizenic.bz" ),
            "cd"      =>  array( "whois.nic.cd" ),
            "ck"      =>  array( "whois.nic.ck" ),
            "cl"      =>  array( "nic.cl" ),
            "coop"    =>  array( "whois.nic.coop" ),
            "cx"      =>  array( "whois.nic.cx" ),
            "cy"      =>  array( "whois.ripe.net" ),
            "cz"      =>  array( "whois.nic.cz" ),
            "dk"      =>  array( "whois.dk-hostmaster.dk" ),
            "dm"      =>  array( "whois.nic.cx" ),
            "dz"      =>  array( "whois.ripe.net" ),
            "ee"      =>  array( "whois.eenet.ee" ),
            "eg"      =>  array( "whois.ripe.net" ),
            "es"      =>  array( "whois.ripe.net" ),
            "fi"      =>  array( "whois.ficora.fi" ),
            "fo"      =>  array( "whois.ripe.net" ),
            "gb"      =>  array( "whois.ripe.net" ),
            "ge"      =>  array( "whois.ripe.net" ),
            "gl"      =>  array( "whois.ripe.net" ),
            "gm"      =>  array( "whois.ripe.net" ),
            "gov"     =>  array( "whois.nic.gov" ),
            "gr"      =>  array( "whois.ripe.net" ),
            "gs"      =>  array( "whois.adamsnames.tc" ),
            "hm"      =>  array( "whois.registry.hm" ),
            "hn"      =>  array( "whois2.afilias-grs.net" ),
            "hr"      =>  array( "whois.ripe.net" ),
            "hu"      =>  array( "whois.ripe.net" ),
            "il"      =>  array( "whois.isoc.org.il" ),
            "int"     =>  array( "whois.isi.edu" ),
            "iq"      =>  array( "vrx.net" ),
            "ir"      =>  array( "whois.nic.ir" ),
            "is"      =>  array( "whois.isnic.is" ),
            "je"      =>  array( "whois.je" ),
            "jp"      =>  array( "whois.jprs.jp" ),
            "kg"      =>  array( "whois.domain.kg" ),
            "kr"      =>  array( "whois.nic.or.kr" ),
            "la"      =>  array( "whois2.afilias-grs.net" ),
            "lt"      =>  array( "whois.domreg.lt" ),
            "lu"      =>  array( "whois.restena.lu" ),
            "lv"      =>  array( "whois.nic.lv" ),
            "ly"      =>  array( "whois.lydomains.com" ),
            "ma"      =>  array( "whois.iam.net.ma" ),
            "mc"      =>  array( "whois.ripe.net" ),
            "md"      =>  array( "whois.nic.md" ),
            "mil"     =>  array( "whois.nic.mil" ),
            "mk"      =>  array( "whois.ripe.net" ),
            "ms"      =>  array( "whois.nic.ms" ),
            "mt"      =>  array( "whois.ripe.net" ),
            "mu"      =>  array( "whois.nic.mu" ),
            "my"      =>  array( "whois.mynic.net.my" ),
            "nf"      =>  array( "whois.nic.cx" ),
            "pl"      =>  array( "whois.dns.pl" ),
            "pr"      =>  array( "whois.nic.pr" ),
            "pt"      =>  array( "whois.dns.pt" ),
            "sa"      =>  array( "saudinic.net.sa" ),
            "sb"      =>  array( "whois.nic.net.sb" ),
            "sg"      =>  array( "whois.nic.net.sg" ),
            "sh"      =>  array( "whois.nic.sh" ),
            "si"      =>  array( "whois.arnes.si" ),
            "sk"      =>  array( "whois.sk-nic.sk" ),
            "sm"      =>  array( "whois.ripe.net" ),
            "st"      =>  array( "whois.nic.st" ),
            "su"      =>  array( "whois.ripn.net" ),
            "tc"      =>  array( "whois.adamsnames.tc" ),
            "tf"      =>  array( "whois.nic.tf" ),
            "th"      =>  array( "whois.thnic.net" ),
            "tj"      =>  array( "whois.nic.tj" ),
            "tk"      =>  array( "whois.nic.tk" ),
            "tl"      =>  array( "whois.domains.tl" ),
            "tm"      =>  array( "whois.nic.tm" ),
            "tn"      =>  array( "whois.ripe.net" ),
            "to"      =>  array( "whois.tonic.to" ),
            "tp"      =>  array( "whois.domains.tl" ),
            "tr"      =>  array( "whois.nic.tr" ),
            "ua"      =>  array( "whois.ripe.net" ),
            "uy"      =>  array( "nic.uy" ),
            "uz"      =>  array( "whois.cctld.uz" ),
            "va"      =>  array( "whois.ripe.net" ),
            "vc"      =>  array( "whois2.afilias-grs.net" ),
            "ve"      =>  array( "whois.nic.ve" ),
            "vg"      =>  array( "whois.adamsnames.tc" ),
            "yu"      =>  array( "whois.ripe.net")
        );

        // check if tld is in list
        if( !empty( $tld ) && array_key_exists( $tld, $l ) ) return $l[$tld];

        // return default server
        return is_array( $default ) ? $default : array( $default );
    }

}





