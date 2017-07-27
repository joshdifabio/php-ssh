<?php
namespace SSH2\Connection\Sessions;

abstract class Signal
{
    const ABRT = 'ABRT';
    const ALRM = 'ALRM';
    const FPE = 'FPE';
    const HUP = 'HUP';
    const ILL = 'ILL';
    const INT = 'INT';
    const KILL = 'KILL';
    const PIPE = 'PIPE';
    const QUIT = 'QUIT';
    const SEGV = 'SEGV';
    const TERM = 'TERM';
    const USR1 = 'usr1';
    const USR2 = 'usr2';
}
