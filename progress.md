# zaemit_plugin 작업 진행 기록

## Next tasks
- [ ] DB 마이그레이션 실행: phpMyAdmin에서 `db/migrate_approval_metadata.sql` 실행 (approval_documents에 metadata JSON 컬럼 추가)
- [ ] E2E 테스트: 카드경비 상세 → 결재 상신 → 결재문서 승인 → 자동 정산 확인
- [ ] 휴가 → 연차 연동 (선행: annual_leave 스키마 설계)
- [ ] 야근 → 수당 연동 (선행: attendance_records를 init.sql에 역기입 / SSOT 복구)

## Deferred items
- **결재선 자동 매칭 엔진**: 현재 경비청구서 상신 시 결재자 "최민호" 고정. 나중에 `approval_lines.line_data` 기반 매칭 로직 도입 시점 결정 필요 (→ 인증 모듈 도입과 함께)
- **RBAC 결재자 본인 확인**: 현재 approval_view.php 승인/반려 버튼은 세션 인증 없어 전체 노출. 세션 도입 후 본인 결재단계만 노출하도록 수정
- **카드 경비 외 다른 워크플로우 5종**: 휴가/야근/출장/4대보험/카드관리 연동 — 같은 훅 패턴(`processApprovedDocument` else-if) 재사용 예정

## Recent decisions
- **2026-07-03** 계정과목 코드 단일화: 정본=5자리 차트(schema_tax.sql 91계정, migrate_account_codes.sql 매핑). 이 DB에 풀차트 시드(39→81계정) + 거래/패턴 옛코드 이관 완료(고아 코드 0). classify.php 정적규칙도 5자리로 교체 — 앞으로 새 계정 규칙 추가 시 반드시 account_categories에 실존하는 코드만 사용.
- **2026-07-03** 분류 학습 엔진 가동: 확정→패턴 생성/강화(learn.php), 규칙 없으면 과거 확정거래 검색제안(rag.php). LLM은 provider 미정 — rag.php bank_rag_llm() seam만 존재. 규칙 관리 UI는 계좌관리>분류규칙 탭에 이미 있음(api/ai.php).
- **2026-07-03** 분류 화면 UX: AI분류 후 드롭다운 버튼을 pill로 갈아끼우던 변조 제거(재선택 불가+정렬 붕괴 원인). 아이콘 자리 상시 예약으로 열 정렬 고정.
- **2026-04-10** 카드관리 > 카드등록 탭을 "카드목록"으로 개명 + 조회 전용으로 전환. 신규등록 버튼, 편집/삭제 아이콘, 사용여부 토글, 등록/수정 모달 및 관련 JS 함수(openCardModal/editCard/saveCard/deleteCard/toggleCard) 제거. 검색 필터와 리스트 표시는 유지. 환경설정 > 카드등록으로 CRUD가 이동했기 때문에 중복 제거. 안내 배너 추가로 사용자 혼선 방지.
- **2026-04-10** Phase 1 워크플로우 통합 1번째: 경비청구서 → 회계 연동 구현 완료. approval_documents에 metadata JSON 컬럼 추가해 원본 데이터(card_expense_id) 역추적 가능하게 함. 승인 훅은 api/approval.php:310 `approveDocument()` 내 트랜잭션 안에 `processApprovedDocument()` 호출로 분리 → 훅 실패 시 결재 상태 변경도 롤백됨. 팀장 피드백 우선순위(휴가→야근→카드) 대신 실사 기반 순서(카드→휴가→야근)로 진행 — 카드 관련 테이블이 이미 다 존재하고 annual_leave 테이블이 아예 없어 선행 공사가 필요한 점 때문.
- **2026-04-10** 팀장 피드백 리포트 분석 완료. 9개 영역 지적 중 Phase 1(워크플로우 연결·RBAC·게시판 댓글·전자서명) 우선.

## Error Log
(없음)
