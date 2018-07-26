<div class="container">
    <h1>Development settings</h1>
    <form class="form-horizontal" action="" method="post" role="form" data-parsley-validate>
        <fieldset>
            <legend>PHP backend control</legend>
            <div class="boxed-group">
				<p>Just some handy "tools" for PHP backend management.</p>
				<div class="form-group">
					<label class="col-sm-2 control-label">Clear PHP 5.5 OPcache</label>
					<div class="col-sm-10">
						<a class="btn btn-default btn-lg btn-lg" type="submit" href="/clear" name="syscmd" id="syscmd-viewphpcache" target="_blank" <?php if($this->opcache === '0'): ?> disabled <?php endif ?>>clear OPcache</a>
					</div>
				</div>            
				<div class="form-group">
					<label class="col-sm-2 control-label">View PHP 5.5 OPcache status</label>
					<div class="col-sm-10">
						<a class="btn btn-default btn-lg btn-lg" type="submit" href="/command/opcache.php" name="syscmd" id="syscmd-viewphpcache" target="_blank">php cache status</a>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">Restart PHP service</label>
					<div class="col-sm-10">
						<input class="btn btn-default btn-lg btn-lg" type="submit" name="syscmd" value="phprestart" id="syscmd-phprestart" <?php if($this->dev !== '1'): ?> disabled <?php endif ?>>
					</div>
				</div> 
			<div class="form-group">
					<label class="col-sm-2 control-label">PHP 5.5 OPcache (persistent cache)</label>
					<div class="col-sm-10">
							<label class="switch-light well" onclick="">
								<input id="opcache" name="opcache[enable]" type="checkbox" value="1"<?php if($this->opcache === '1'): ?> checked="checked" <?php endif ?>>
								<span><span>OFF</span><span>ON</span></span><a class="btn btn-primary"></a>
							</label>
						<span class="help-block">Enable PHP 5.5 OPcache persistence. This drastically speeds up page render, but you must manually clear cache (use above button) at any source code change. This is enabled by default in production environment</span>
					</div>
				</div>
			</div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button class="btn btn-primary btn-lg" value="1" name="opcache[submit]" type="submit">Save settings</button>
                </div>
            </div>
        </fieldset>
    </form>
    <form class="form-horizontal" action="" method="post" role="form" data-parsley-validate>
        <fieldset>
            <legend>DevTeam functions</legend>
            <div class="boxed-group">
                <div class="form-group">
                    <label class="col-sm-2 control-label">PlayerID</label>
                    <div class="col-sm-10">
                        <input class="form-control input-lg" type="text" id="playerid" name="playerid" value="<?php echo $this->playerid; ?>" disabled autocomplete="off">
                        <input class="form-control input-lg" type="text" id="hwplatformid" name="hwplatformid" value="<?php echo $this->hwplatformid; ?>" disabled autocomplete="off">
                        <span class="help-block">Current detected HW fingerprint.</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Git branch</label>
                    <div class="col-sm-10">
                        <input class="form-control input-lg" type="text" id="gitbranch" name="gitbranch" value="<?php echo $this->gitbranch; ?>" disabled autocomplete="off">
                        <span class="help-block">Current Git branch.</span>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-sm-2 control-label">Reset the RuneAudio player</label>
                    <div class="col-sm-10">
                        <input class="btn btn-default btn-lg" type="submit" name="syscmd" value="blankplayerid" id="syscmd-blankplayerid" <?php if($this->dev !== '1'): ?> disabled <?php endif ?>>
                        <span class="help-block">Reset playerID and hwplatformID. The player will perform configuration initialisation routines during the next reboot.<br>
						This function can be used as an alternative to re-flashing your Micro-SD card if Rune stops working correctly. In many cases it will fix the problems.<br> 
						Always <strong>de-install</strong> Rern's Addons <strong>before</strong> choosing this option!<br>
						You will <strong>lose most of your settings</strong> after choosing this option!<br>
						</span>
                    </div>
                </div>                
                <div class="form-group">
                    <label class="col-sm-2 control-label">Clear installation</label>
                    <div class="col-sm-10">
                        <input class="btn btn-default btn-lg" type="submit" name="syscmd" value="clearimg" id="syscmd-clearimg" <?php if($this->dev !== '1'): ?> disabled <?php endif ?>>
                        <span class="help-block">Clear command history, logs, reset image parameters to default settings.<br>
                        NOTE: (Dev team function) Use this function prior to publication of a RuneOS image.<br>
						This function takes up to 20 minutes to complete, wait, be patient!<br>
                        WARNING: Automatic system shutdown after execution!</span>
                    </div>
                </div>
            </div>
            <div class="boxed-group">
				<div class="form-group">
					<label class="col-sm-2 control-label">Dev Mode</label>
					<div class="col-sm-10">
							<label class="switch-light well" onclick="">
								<input id="opcache" name="mode[dev][enable]" type="checkbox" value="1"<?php if($this->dev === '1'): ?> checked="checked" <?php endif ?>>
								<span><span>OFF</span><span>ON</span></span><a class="btn btn-primary"></a>
							</label>
							<span class="help-block">Enable <i>developer mode</i>.<br>
							When developer mode is ON:<br>
							- The functions on this page are switched ON<br>
							- Samba is switched on in read/write mode (default is without password control)<br>
							- JavaScript uses the runeaudio.js insted of runeaudio.min.js which is normally used</span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">Debug</label>
					<div class="col-sm-10">
							<label class="switch-light well" onclick="">
								<input id="opcache" name="mode[debug][enable]" type="checkbox" value="1"<?php if($this->debug === '1'): ?> checked="checked" <?php endif ?>>
								<span><span>OFF</span><span>ON</span></span><a class="btn btn-primary"></a>
							</label>
						<span class="help-block">Activate debug data collection. (You will find all log files in <strong>/var/log/runeaudio/</strong> directory)</span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">Check FS permissions</label>
					<div class="col-sm-10">
						<input class="btn btn-default btn-lg" type="submit" name="syscmd" value="syschmod" id="syscmd-mpdrestart" <?php if($this->dev !== '1'): ?> disabled <?php endif ?>>
						<span class="help-block">Check and restore the correct FS ownership and permissions in important system areas</span>
					</div>
				</div>
            </div>
            <div class="boxed-group">
				<div class="form-group">
					<label class="col-sm-2 control-label">SoXr for MPD</label>
					<div class="col-sm-10">
							<label class="switch-light well" onclick="">
								<input id="opcache" name="mode[soxrmpdonoff][enable]" type="checkbox" value="1"<?php if($this->soxrmpdonoff === '1'): ?> checked="checked" <?php endif ?>>
								<span><span>OFF</span><span>ON</span></span><a class="btn btn-primary"></a>
							</label>
						<span class="help-block">SoXr for MPD is set ON or OFF depending on processor type. The MPD sample rate converter can not be switched off, it does nothing (and has no CPU overhead) unless sample rate conversion is required.
						This happens only in special circumstances, MPD switches it on automatically when required. It always reduces quality when it operates, but SoXr is better than the built-in MPD sample-rate converter.
						A higher CPU overhead is imposed by SoxR than when using the built-in MPD sample-rate converter. You can can override the default setting here</span>
					</div>
				</div>
            </div>
            <div class="boxed-group">
				<div class="form-group">
					<label class="col-sm-2 control-label">SoXr for Airplay</label>
					<div class="col-sm-10">
							<label class="switch-light well" onclick="">
								<input id="opcache" name="mode[soxrairplayonoff][enable]" type="checkbox" value="1"<?php if($this->soxrairplayonoff === '1'): ?> checked="checked" <?php endif ?>>
								<span><span>OFF</span><span>ON</span></span><a class="btn btn-primary"></a>
							</label>
						<span class="help-block">SoXr for Airplay is set OFF by default. Your system <strong>will crash</strong> if it is switched ON, please leave it switched OFF.<br>
						<i>In theory it should improve the quality of the 'synchronisation' of Airplay streams, read the shairport-sync documentation for details.
						Unfortunately it imposes a too high CPU overhead for the processors used by RuneAudio</i></span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">Airplay Metadata</label>
					<div class="col-sm-10">
							<label class="switch-light well" onclick="">
								<input id="opcache" name="mode[metadataairplayonoff][enable]" type="checkbox" value="1"<?php if($this->metadataairplayonoff === '1'): ?> checked="checked" <?php endif ?>>
								<span><span>OFF</span><span>ON</span></span><a class="btn btn-primary"></a>
							</label>
						<span class="help-block">Metadata for Airplay is set ON or OFF depending on processor type.
						It imposes a medium CPU and memory overhead when Airplay is active.
						You can can override the default setting here</span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">Airplay Cover Art</label>
					<div class="col-sm-10">
							<label class="switch-light well" onclick="">
								<input id="opcache" name="mode[artworkairplayonoff][enable]" type="checkbox" value="1"<?php if($this->artworkairplayonoff === '1'): ?> checked="checked" <?php endif ?>>
								<span><span>OFF</span><span>ON</span></span><a class="btn btn-primary"></a>
							</label>
						<span class="help-block">Cover Art for Airplay is set ON or OFF depending on processor type.
						When set on it imposes a CPU overhead when Airplay is active. On less powerful processors it can fail to work properly.
						You can can override the default setting here. Cover Art is also automatically switched off when 'Airplay Metadata' is off</span>
					</div>
				</div>
			</div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button class="btn btn-primary btn-lg" value="1" name="mode[debug][submit]" type="submit">Save settings</button>
                </div>
            </div>
        </fieldset>
    </form>
    <form class="form-horizontal" method="post">
        <fieldset>
            <legend>System commands</legend>
            <p>Just some handy system commands, without the hassle of logging into SSH.</p>
            <div class="boxed-group">
				<div class="form-group">
					<label class="col-sm-2 control-label">Update RuneUI</label>
					<div class="col-sm-10">
						<input class="btn btn-default btn-lg" type="submit" name="syscmd" value="gitpull" id="syscmd-gitpull" <?php if($this->dev !== '1'): ?> disabled <?php endif ?>>
						<span class="help-block">Download and install the latest updates, a reboot is recommended after updating</span>
					</div>
				</div>
            </div>
            <div class="boxed-group">
				<div class="form-group">
					<label class="col-sm-2 control-label">Restart MPD service</label>
					<div class="col-sm-10">
						<input class="btn btn-default btn-lg" type="submit" name="syscmd" value="mpdrestart" id="syscmd-mpdrestart" <?php if($this->dev !== '1'): ?> disabled <?php endif ?>>
						<span class="help-block">&nbsp;</span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">Restart Samba</label>
					<div class="col-sm-10">
						<input class="btn btn-default btn-lg" type="submit" name="syscmd" value="sambarestart" id="syscmd-sambarestart" <?php if($this->dev !== '1'): ?> disabled <?php endif ?>>
						<span class="help-block">If you change the Samba configuration files a restart is required to activate your changes</span>
					</div>
				</div>
            </div>
            <div class="boxed-group">
				<div class="form-group">
					<label class="col-sm-2 control-label">Reset NET config</label>
					<div class="col-sm-10">
						<input class="btn btn-default btn-lg" type="submit" name="syscmd" value="netconfreset" id="syscmd-netconfreset" <?php if($this->dev !== '1'): ?> disabled <?php endif ?>>
						<span class="help-block">&nbsp;</span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">Reset MPD config</label>
					<div class="col-sm-10">
						<input class="btn btn-default btn-lg" type="submit" name="syscmd" value="mpdconfreset" id="syscmd-mpdconfreset" <?php if($this->dev !== '1'): ?> disabled <?php endif ?>>
						<span class="help-block">&nbsp;</span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">Reset Airplay config</label>
					<div class="col-sm-10">
						<input class="btn btn-default btn-lg" type="submit" name="syscmd" value="airplayconfreset" id="syscmd-airplayconfreset" <?php if($this->dev !== '1'): ?> disabled <?php endif ?>>
						<span class="help-block">This will reset the shairport-sync.conf file back to defaults and reinitialise Airplay</span>
					</div>
				</div>
            </div>
            <div class="boxed-group">
				<div class="form-group">
					<label class="col-sm-2 control-label">Install Rern's Addons</label>
					<div class="col-sm-10">
						<input class="btn btn-default btn-lg" type="submit" name="syscmd" value="rerninstall" id="syscmd-rerninstall" <?php if($this->dev !== '1'): ?> disabled <?php endif ?>>
						<span class="help-block">See the RuneAudio forum for details: <a href="http://www.runeaudio.com/forum/addons-menu-install-addons-the-easy-way-t5370.html#p22376" target="_blank" rel="nofollow">Addons Menu - Install addons the easy way</a> <br>
						Refresh the browser after installing, this should enable the Addons menu.<br>
						You can remove the Rern's Addons menu via the Addons menu.<br>
						The functionality within Rern's Addons menu is not supported by the RuneAudio team, but you can get help via the forum</span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">Re-install Rern's Addons</label>
					<div class="col-sm-10">
						<input class="btn btn-default btn-lg" type="submit" name="syscmd" value="rernreinstall" id="syscmd-rernreinstall" <?php if($this->dev !== '1'): ?> disabled <?php endif ?>>
						<span class="help-block">If Rern's Addons menu stops working, or the install command above fails, or you cannot de-install it; this may fix it</span>
					</div>
				</div>
			</div>
        </fieldset>
    </form>
    <form class="form-horizontal" method="post">
        <fieldset>
            <legend>Background WORKERS control</legend>
            <div class="boxed-group">
				<p>Just some handy "tools" for Background WORKERS management.</p>
				<div class="form-group">
					<label class="col-sm-2 control-label">RuneAudio SYstem Worker (rune_SY_wrk)</label>
					<div class="col-sm-10">
						<button class="btn btn-default btn-lg" value="rune_SY_wrk" name="syscmd[wrkrestart]" type="submit">Restart rune_SY_wrk</button>
						<span class="help-block">&nbsp;</span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-sm-2 control-label">RuneAudio PLayback Worker (rune_PL_wrk)</label>
					<div class="col-sm-10">
						<button class="btn btn-default btn-lg" value="rune_PL_wrk" name="syscmd[wrkrestart]" type="submit">Restart rune_PL_wrk</button>
						<span class="help-block">&nbsp;</span>
					</div>
				</div>    
				<div class="form-group">
					<label class="col-sm-2 control-label">RuneAudio Shairport-sync Metadata decoder Worker (rune_SSM_wrk)</label>
					<div class="col-sm-10">
						<button class="btn btn-default btn-lg" value="rune_SSM_wrk" name="syscmd[wrkrestart]" type="submit">Restart rune_SSM_wrk</button>
						<span class="help-block">&nbsp;</span>
					</div>
				</div>    
			</div>    
        </fieldset>
    </form>
    <form class="form-horizontal" method="post">
        <fieldset>
            <legend>Notes</legend>
            <div class="boxed-group">
				<p>Just some notes concerning features and options</p>
				<div class="form-group">
					<label class="col-sm-2 control-label">Patched Linux kernel for audio output up to 384k</label>
					<div class="col-sm-10">
						<span class="help-block">This version of RuneAudio has a patched kernel which allows alsa to stream at a sample-rate up to 384k.
						This is twice as high as the normal maximum rate of 192k.
						Where necessary the parameters for selecting and setting up hardware audio cards have been adapted to give the best performance.<br>
						If you manually update the kernel, not only will you lose the 384k feature but some of the setting for hardware audio cards will no longer be optimal</span>
					</div>
				</div>    
				<div class="form-group">
					<label class="col-sm-2 control-label">Hard-disk drive monitoring</label>
					<div class="col-sm-10">
						<span class="help-block">Only when you have a hard-disk drive attached to Rune:<br>
						Hard disk health-check monitoring will automatically be carried out using 'smartmontools'.<br>
						Normally you will not notice that this is taking place. In the debug listing there is a status display.<br>
						Real-time monitoring is carried out when the player is active. If something starts to go amiss with your hard-disk drive you will alerted every 1,5 minutes via the UI</span>
					</div>
				</div>    
				<div class="form-group">
					<label class="col-sm-2 control-label">Missing bit-rates in the player UI</label>
					<div class="col-sm-10">
						<span class="help-block">For some reason MPD is incapable of providing the bit-rate for some audio file types.
						This results in the bit-rate not being displayed in the player UI.<br>
						A solution for this problem has been implemented but not fully included in this version in order to minimise the image size.
						If missing bit-rates is a problem for you, installing the package 'mediainfo' may provide a solution. This is quite a large package and you should first extend the Linux Partition on your Micro-SD card.<br>
						You can find instructions for extending the Linux partition <a href="http://www.runeaudio.com/documentation/troubleshooting/extend-partition-sd/" title="EXTEND A PARTITION" rel="nofollow" target="_blank">here</a>.<br>
						Then you can use the following command to install the 'mediainfo' package: <strong>pacman -Sy mediainfo</strong></span>
					</div>
				</div>
			</div>
        </fieldset>
    </form>
<!-- <form class="form-horizontal" method="post">
        <fieldset>
            <legend>Backup / Restore configuration</legend>
            <p>&nbsp;</p>
            <div class="form-group">
                <label class="col-sm-2 control-label">Backup player config</label>
                <div class="col-sm-10">
                    <input class="btn btn-default btn-lg" type="submit" name="syscmd" value="backup" id="syscmd-backup">
                </div>
            </div>
                    </fieldset>
    </form>
    <form class="form-horizontal" method="post">
        <fieldset>
            <div class="form-group" >
                <label class="col-sm-2 control-label" for="port">Configuration file</label>
                <div class="col-sm-10">
                    <div class="fileupload fileupload-new" data-provides="fileupload">
                      <span class="btn btn-file"><span class="fileupload-new">restore</span><span class="fileupload-exists">Change</span><input type="file" /></span>
                      <span class="fileupload-preview"></span>
                      <a href="#" class="close fileupload-exists" data-dismiss="fileupload" style="float: none">×</a>
                    </div>            
                </div>
            </div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button class="btn btn-primary btn-lg" value="restore" name="syscmd" type="submit">Restore config</button>
                </div>
            </div>
        </fieldset>
    </form> -->
</div>