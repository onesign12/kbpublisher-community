---

maps:    

  app:
    dirs:
      - "{{ app_dir }}/admin/modules" 
      - "{{ app_dir }}/admin/cron"
      - "{{ app_dir }}/plugins"
      - "{{ app_dir }}/admin/lib/core"
      - "{{ app_dir }}/admin/lib/eleontev"
      - "{{ app_dir }}/client/inc"
    skip:
      #dir: "/templates/"
      regex: "/PageController\\.php/"
     
  setup:
    dirs:
      - "{{ app_dir }}/setup/inc" 
  
  # admin:
  #   dirs:
  #     - "{{ app_dir }}/admin/modules" 
  #     - "{{ app_dir }}/admin/cron"
  #     - "{{ app_dir }}/admin/extra"
  #   skip:
  #     dir: "templates"
  #     regex: "/PageController/"
  # 
  # lib:
  #   dirs:
  #     - "{{ app_dir }}/admin/lib/core"
  #     - "{{ app_dir }}/admin/lib/eleontev"
  # 
  # client:
  #   dirs:
  #     - "{{ app_dir }}/client/inc"