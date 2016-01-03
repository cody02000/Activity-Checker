#Activity Checker Mod
Activity Checker, a mod for Simple Machines Forum, checks whether a member is inactive or active based on usergroup and last post.

##Features
* Newly Inactive Member List - Members who are in the active group but are currently inactive.
* Newly Active Member List - Members who are in the inactive group but are currently active.
* Never Posted Member List - Members in any group who have a post count of 0.  If they have posted in a board that does not count posts, it will display the last such post.
* Send PM to users moved to the inactive group.  Specify Subject, Message, BCC recipients and specify which account the PM is sent from (account must have the ability to manage membergroups to show up on the list.)
* Send Email to users removed via the Never Posted List.  Set the message and subject.
* Define what amount of time, in weeks, is considered inactive.
* Check for post activity by Category

##After Installing:
For the mod to work, active and inactive membergroups must be specified in the general settings.  As well as which categories to check.  PM to Inactive Members and Email to Deleted Members must be enabled to use those features.  If a subject and message are not specified in the settings, a default subject and message will be used.

##License
Copyright (c) 2016, Cody Williams
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice, 
	this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright notice, 
	this list of conditions and the following disclaimer in the documentation 
	and/or other materials provided with the distribution.

	3. Neither the name of the copyright holder nor the names of its contributors
	may be used to endorse or promote products derived from this software without
	specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" 
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE 
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE 
ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE 
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY 
THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED 
OF THE POSSIBILITY OF SUCH DAMAGE.
