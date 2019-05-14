<?php

ITSEC_Core::get_scheduler()->schedule( ITSEC_Scheduler::S_TWICE_HOURLY, 'check-grade-report' );