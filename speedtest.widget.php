<?php

/*
 * speedtest.widget.php
 *
 * Copyright (c) 2020 Alon Noy (only works with the not official speedtest cli that is no longer supported and does give wrong results)
 * The original by Alon Noy can be found here: https://github.com/aln-1/pfsense-speedtest-widget
 * Copyright (c) 2024 Leon Straathof (modified version to work with official speedtest cli)
 *
 * Licensed under the GPL, Version 3.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * https://www.gnu.org/licenses/gpl-3.0.txt
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 
 * EXAMPLE JSON DATA FORMAT FROM THE OFFICIAL SPEEDTEST CLI (VERSION 1.2.0.84-1)
   {"type":"result",
	"timestamp":"2024-01-05T10:09:25Z",
	"ping":{"jitter":0.043,"latency":4.752,"low":4.727,"high":4.799},
	"download":{"bandwidth":117318968,"bytes":692084632,"elapsed":5914,"latency":{"iqm":21.800,"low":4.676,"high":24.308,"jitter":0.680}},
	"upload":{"bandwidth":110904809,"bytes":1097799285,"elapsed":10311,"latency":{"iqm":4.714,"low":3.697,"high":7.739,"jitter":0.416}},
	"packetLoss":0,
	"isp":"T-Mobile Netherlands",
	"interface":{"internalIp":"123.123.123.123","name":"vmx0","macAddr":"0A:1B:2C:3D:4E:5F","isVpn":false,"externalIp":"123.123.123.123"},
	"server":{"id":13883,"host":"speedtest.trined.nl","port":8080,"name":"TriNed B.V.","location":"Sint-Oedenrode","country":"Netherlands","ip":"45.145.108.44"},
	"result":{"id":"12345678-abcd-1234-abcd-123456789abc","url":"https://www.speedtest.net/result/c/12345678-abcd-1234-abcd-123456789abc","persisted":true}}

  New:	--json {"download": 231872650.97135112, "upload": 5540228.640909488, "ping": 25.052, "server": {"url": "http://test.nextlevel.net:8080/speedtest/upload.php", "lat": "37.3541", "lon": "-121.9552", "name": "Santa Clara, CA", "country": "United States", "cc": "US", "sponsor": "Next Level Infrastructure", "id": "25606", "host": "test.nextlevel.net:8080", "d": 143.47856656623335, "latency": 25.052}, "timestamp": "2024-03-01T04:12:57.800204Z", "bytes_sent": 7258112, "bytes_received": 290060371, "share": null, "client": {"ip": "73.90.27.220", "lat": "38.5569", "lon": "-121.3627", "isp": "Comcast Cable", "isprating": "3.7", "rating": "0", "ispdlavg": "0", "ispulavg": "0", "loggedin": "0", "country": "US"}}
  
INSTALL
-------
Goto https://www.speedtest.net/apps/cli
Click FreeBSD and find URL of newest version.
Diagnotics-->Command Prompt-->Execute Shell Command:	
	env ABI=FreeBSD:13:x86:64 pkg add "https://install.speedtest.net/app/cli/ookla-speedtest-1.2.0-freebsd13-x86_64.pkg"
	(Use the URL found on the speedtest.net website and the FreeBSD version number in env ABI must match the version number in the URL)
Diagnotics-->Command Prompt-->Execute Shell Command:
	speedtest --accept-license
Diagnotics-->Command Prompt-->Execute Shell Command:
	speedtest --accept-gdpr
Diagnotics-->Command Prompt-->Upload File: 
	speedtest.widget.php
Diagnotics-->Command Prompt-->Execute Shell Command:
	mv -f /tmp/speedtest.widget.php /usr/local/www/widgets/widgets/
Status-->Dashboard:
	Add the speedtest widget.
	
UNINSTALL
---------	
Diagnotics-->Command Prompt-->Execute Shell Command:
	pkg info | grep speedtest
Diagnotics-->Command Prompt-->Execute Shell Command:	
	pkg delete -y speedtest-1.2.0.84-1.ea6b6773cf
	(use the package name found in the first step)
Status-->Dashboard:
	Remove the speedtest widget.
Diagnotics-->Command Prompt-->Execute Shell Command:
	rm -f /usr/local/www/widgets/widgets/speedtest.widget.php
 */

require_once("guiconfig.inc");

if ($_REQUEST['ajax']) { 
    $results = shell_exec("speedtest --json");
    if(($results !== null) && (json_decode($results) !== null)) {
        $config['widgets']['speedtest_result'] = $results;
        write_config("Save speedtest results");
        echo $results;
    } else {
        echo json_encode(null);
    }
} else {
    $results = isset($config['widgets']['speedtest_result']) ? $config['widgets']['speedtest_result'] : null;
    if(($results !== null) && (!is_object(json_decode($results)))) {
        $results = null;
    }
?>
<table class="table">
	<tr>
		<td><h4>Ping <i class="fa fa-exchange"></h4></td>
		<td><h4>Download <i class="fa fa-download"></i></h4></td>
		<td><h4>Upload <i class="fa fa-upload"></h4></td>
	</tr>
	<tr>
		<td><h4 id="speedtest-ping">N/A</h4></td>
		<td><h4 id="speedtest-download">N/A</h4></td>
		<td><h4 id="speedtest-upload">N/A</h4></td>
	</tr>
	<tr>
		<td>ISP</td>
		<td colspan="2" id="speedtest-isp">N/A</td>
	</tr>
	<tr>
		<td>Host</td>
		<td colspan="2" id="speedtest-host">N/A</td>
	</tr>
	<tr>
		<td colspan="3" id="speedtest-ts" style="font-size: 0.8em;">&nbsp;</td>
	</tr>
</table>
<a id="updspeed" href="#" class="fa fa-refresh" style="display: none;"></a>
<a id="Ookla" href="#" target="_blank" style="display: none;"> <i class="fa fa-external-link"></i></a>
<script type="text/javascript">
function update_result(results) {
    if(results != null) {
    	var date = new Date(results.timestamp);
    	$("#speedtest-ts").html(date);
    	$("#speedtest-ping").html(results.ping.toFixed(2) + "<small> ms</small>");
    	$("#speedtest-download").html((results.download / 1000000).toFixed(2) + "<small> Mbps</small>");
    	$("#speedtest-upload").html((results.upload / 1000000).toFixed(2) + "<small> Mbps</small>");
    	$("#speedtest-isp").html(results.client.isp + "<small> (" + results.client.ip + ")</small>");
    	$("#speedtest-host").html(results.server.sponsor + " (" + results.server.name + ") " + "<small>[" + results.server.d.toFixed(2) + " km]  " + results.server.latency.toFixed(2) + " ms</small><br><small>(" + results.server.host + ")</small>");
		$("#Ookla").attr("href", results.server.url);
		$("#Ookla").show();
    } else {
    	$("#speedtest-ts").html("Speedtest failed");
    	$("#speedtest-ping").html("N/A");
    	$("#speedtest-download").html("N/A");
    	$("#speedtest-upload").html("N/A");
    	$("#speedtest-upload").html("N/A");
    	$("#speedtest-isp").html("N/A");
    	$("#speedtest-host").html("N/A");
    }
}

function update_speedtest() {
    $('#updspeed').off("click").blur().addClass("fa-spin").click(function() {
        $('#updspeed').blur();
        return false;
    });
    $.ajax({
        type: 'POST',
        url: "/widgets/widgets/speedtest.widget.php",
        dataType: 'json',
        data: {
            ajax: "ajax"
        },
        success: function(data) {
            update_result(data);
        },
        error: function() {
            update_result(null);
        },
        complete: function() {
            $('#updspeed').off("click").removeClass("fa-spin").click(function() {
                update_speedtest();
                return false;
            });
        }
    });
}
events.push(function() {
	var target = $("#updspeed").closest(".panel").find(".widget-heading-icon");
	$("#Ookla").prependTo(target);
	$("#updspeed").prependTo(target).show();
    $('#updspeed').click(function() {
        update_speedtest();
        return false;
    });
    update_result(<?php echo ($results === null ? "null" : $results); ?>);
});
</script>
<?php } ?>
