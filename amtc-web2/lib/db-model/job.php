<?php
class Job extends Model {
  const JOB_INTERACTIVE = 1;
  const JOB_SCHEDULED   = 2;
  const JOB_MONITORING  = 3;

  const STATUS_PENDING  = 0;
  const STATUS_RUNNING  = 1;
  const STATUS_DONE     = 2;
  const STATUS_ERROR    = 9;

  static $jobTypeMap = Array(
      self::JOB_INTERACTIVE => 'Interactive',
      self::JOB_SCHEDULED   => 'Scheduled',
      self::JOB_MONITORING  => 'Monitoring'
  );
  static $jobStatusMap = Array(
      self::STATUS_PENDING  => 'pending',
      self::STATUS_RUNNING  => 'running',
      self::STATUS_DONE     => 'done',
      self::STATUS_ERROR    => 'error'
  );
}
