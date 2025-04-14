<?php

	class LogUser{
		private int $id, $id_user;
		private DateTime $date_log;
		private string $action_log, $user_agent, $referer, $remote_addr, $request_time, $request_time_str;

		/**
		 * @param int           $id_user
		 * @param DateTime|null $date_log
		 */
		public function __construct(int $id_user, DateTime $date_log = null){
			$this->id_user = $id_user;
			$this->date_log = $date_log;
			$this->action_log = '';
			$this->user_agent = '';
			$this->referer = '';
			$this->remote_addr = '';
			$this->request_time = '';
			$this->request_time_str = '';
		}

		public function getId(): int{
			return $this->id;
		}

		public function setId(int $id): void{
			$this->id = $id;
		}

		public function getIdUser(): int{
			return $this->id_user;
		}

		public function setIdUser(int $id_user): void{
			$this->id_user = $id_user;
		}

		public function getDateLog(): DateTime{
			return $this->date_log;
		}

		public function setDateLog(DateTime $date_log): void{
			$this->date_log = $date_log;
		}

		public function getActionLog(): string{
			return $this->action_log;
		}

		public function setActionLog(string $action_log): void{
			$this->action_log = $action_log;
		}

		public function getUserAgent(): string{
			return $this->user_agent;
		}

		public function setUserAgent(string $user_agent): void{
			$this->user_agent = $user_agent;
		}

		public function getReferer(): string{
			return $this->referer;
		}

		public function setReferer(string $referer): void{
			$this->referer = $referer;
		}

		public function getRemoteAddr(): string{
			return $this->remote_addr;
		}

		public function setRemoteAddr(string $remote_addr): void{
			$this->remote_addr = $remote_addr;
		}

		public function getRequestTime(): string{
			return $this->request_time;
		}

		public function setRequestTime(string $request_time): void{
			$this->request_time = $request_time;
		}

		public function getRequestTimeStr(): string{
			return $this->request_time_str;
		}

		public function setRequestTimeStr(string $request_time_str): void{
			$this->request_time_str = $request_time_str;
		}
	}